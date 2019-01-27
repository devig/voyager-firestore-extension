<?php

use TCG\Voyager\Models\DataType;


$namespace = 'Akwad\VoyagerFirestoreExtension\Http\Controllers';



 Route::group([
            'as'     => 'VoyagerFirestore',
            'prefix' => '/admin/fbread',
            'namespace'=>$namespace
        ], function () {
            Route::get('/', ['uses' => 'FirestoreBreadController@index', 'as' => 'index']);
            Route::get('{table}/create', ['uses' => 'FirestoreBreadController@create','as' => 'create']);
            Route::post('/', ['uses' => 'FirestoreBreadController@store',   'as' => 'store']);
            
            Route::get('{table}/edit', ['uses' => 'FirestoreBreadController@edit','as' => 'edit']);
            Route::put('{id}', ['uses' => 'FirestoreBreadController@update','as' => 'update']);
            
            Route::delete('{id}', ['uses' => 'FirestoreBreadController@destroy','as' => 'delete']);
            
            
        });

?>