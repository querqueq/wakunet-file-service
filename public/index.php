<?php

use App\Models\FileDescriptor;

require __DIR__.'/../vendor/autoload.php';

$app = new Slim\Slim();

$app->error(function(\Exception $e) use ($app) {
    if ($e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
        $app->notFound();
    }
});

function getSender() {
    global $app;
    $sender = $app->request->headers->get('UserId');
    if(empty($sender)) $app->halt('409', 'No sender id');
    return $sender;
}

function returnFileDesc($model) {
    global $app;
    $req = $app->request();
    $app->response()['Content-Type'] = 'application/json';
    $arr = $model->toOutput();
    if($model->isPublic()) {
        $arr['url'] = $req->getUrl().$req->getRootUri()."/public/".$model->id;
    }
    $app->response()->setBody(json_encode($arr, JSON_UNESCAPED_SLASHES));
}

$app->post('/files', function() use ($app) {
    $sender = getSender();
    $fileInfo = end($_FILES);
    $app->log->info(print_r($_FILES, true));

    if(!$fileInfo) $app->halt('409', 'No file uploaded');

    $fileDesc = FileDescriptor::upload($fileInfo, $sender);

    returnFileDesc($fileDesc);
});

$app->get('/files/:fileid', function($fileid) use ($app) {
    getSender();
    $fileDesc = FileDescriptor::findOrFail($fileid);
    $fileDesc->download();
});

$app->get('/public/:fileid', function($fileid) use ($app) {
    $fileDesc = FileDescriptor::findOrFail($fileid);
    if(!$fileDesc->isPublic()) $app->notFound();
    $fileDesc->download();
});

$app->get('/files/:fileid/info', function($fileid) use ($app) {
    getSender();
    $fileDesc = FileDescriptor::findOrFail($fileid);
    returnFileDesc($fileDesc);
});

$app->post('/files/:fileid/info', function($fileid) use ($app) {
    $sender = getSender();
    $fileDesc = FileDescriptor::findOrFail($fileid);

    if($sender != $fileDesc->creator_id) $app->halt('403');

    $body = json_decode($app->request()->getBody(),true);
    if(array_key_exists('uuid', $body) && $body['uuid'] != $fileid) $app->halt('409', 'Ids are not matching');

    $fileDesc->fill($body);
    $app->log->info(print_r($fileDesc,true));
    $fileDesc->save();
    returnFileDesc($fileDesc);
});

$app->run();