<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;

class FileDescriptor extends Eloquent {

    public $incrementing = false;
    protected $guarded = ['file_type'
                         ,'file_size'
                         ,'creator_id'
                         ,'json_append'
                         ,'created_at'
                         ,'updated_at'
                         ,'id'
                         ,'uuid'
                         ,'url'
                         ];

    protected $table = 'file';
    //File modifier
    const MODIFIER = ['private','public'];
    const BASE_DIR = __DIR__."/../..";
    const PUBLIC_UPLOAD_DIR = self::BASE_DIR."/public/public";
    const PRIVATE_UPLOAD_DIR = self::BASE_DIR."/files";

    protected $hidden = ['json_append','id'];

    /**
     * @param array $fileInfo - one element of $_FILES
     * @return instance of this
     */
    public static function upload($fileInfo, $creator = 0) {
        $rtrn = new FileDescriptor;
        $rtrn->file_name = $fileInfo['name'];
        $rtrn->file_type = $fileInfo['type'];
        $rtrn->file_size = $fileInfo['size'];
        $rtrn->creator_id = $creator;
        $rtrn->save();
        move_uploaded_file($fileInfo['tmp_name'], $rtrn->file_path);
        return $rtrn;
    }

    public function download() {
        header("Content-Type: application/octet-stream"); //$this->file_type");
        if($this->isPublic()) {
            header('Pragma: public');
        } else {
            header('Pragma: no-cache');
        }
        header('Content-disposition: attachment; filename="' . $this->file_name.'"');
        header('Content-Transfer-Encoding: binary');
        header('Content-Length: '.filesize($this->file_path));
        ob_clean();
        flush();
        readfile($this->file_path);
        exit();
    }

    public function isPublic() {
        return $this->modifier === 'public';
    }

    public function __get($name) {
        switch($name) {
            case 'file_path':
                return self::PRIVATE_UPLOAD_DIR."/".$this->id;
            default:
                return parent::__get($name);
        }
    }

    public function save() {
        if(empty($this->id)) {
            $this->id = \Ramsey\Uuid\Uuid::uuid4();
        }
        if(!in_array($this->modifier, self::MODIFIER)) $this->modifier = self::MODIFIER[0];
        parent::save();
    }

    public function toOutput() {
        $values = array_merge($this->toArray(),json_decode($this->json_append, true));
        $values['uuid'] = $this->id;
        return $values;
    }

    public function toJson($options = 0) {
        return json_encode($this->toOutput(), $options);
    }

    /**
     * Same as fill from superclass but adds all unkown values to json_append
     */
    public function fill($attributes) {
        $append = [];
        foreach ($attributes as $key => $value) {
            $key = $this->removeTableFromKey($key);
            if ($this->isFillable($key) && array_key_exists($key, $this->getOriginal())) {
                $this->setAttribute($key, $value);
            } elseif(!$this->isGuarded($key)) {
                $append[$key] = $value;
            }
        }
        $existing = json_decode($this->json_append, true);
        if(is_array($existing)) $append = array_merge($existing, $append);
        $this->json_append = json_encode($append, true);
        return $this;
    }
}
