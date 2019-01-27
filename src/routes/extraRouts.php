<?php

Route::group(['prefix' => 'admin'], function () {
  
    try {
        foreach (DataType::all() as $dataType) {
            if(!$dataType->isCollection){
                continue;
            }

            $breadController = $dataType->controller
                             ? $dataType->controller
                             : 'Akwad\VoyagerFirestoreExtension\Http\Controllers\VoyagerBaseController';

            Route::get($dataType->slug.'/order', $breadController.'@order')->name($dataType->slug.'.order');
            Route::post($dataType->slug.'/order', $breadController.'@update_order')->name($dataType->slug.'.order');
            Route::resource($dataType->slug, $breadController);
        }
    } catch (\InvalidArgumentException $e) {
        throw new \InvalidArgumentException("Custom routes hasn't been configured because: ".$e->getMessage(), 1);
    } catch (\Exception $e) {
        // do nothing, might just be because table not yet migrated.
    }

});