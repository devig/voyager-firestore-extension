<?php

namespace Akwad\VoyagerFirestoreExtension\Http\Controllers;

use App\Http\Controllers\Controller;
use Google\Cloud\Firestore\FirestoreClient;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use TCG\Voyager\Events\BreadAdded;
use TCG\Voyager\Events\BreadDeleted;
use TCG\Voyager\Events\BreadUpdated;
use TCG\Voyager\Facades\Voyager;

class FirestoreBreadController extends Controller
{

    public function index(FirestoreClient $Firestore)
    {
        Voyager::canOrFail('browse_bread');
        $dataTypes = Voyager::model('DataType')->select('id', 'name', 'slug')->where('iscollection', true)->get()->keyBy('name')->toArray();

        $tables = array_map(function ($table) use ($dataTypes) {
            $table = [
                'name' => $table,
                'slug' => isset($dataTypes[$table]['slug']) ? $dataTypes[$table]['slug'] : null,
                'dataTypeId' => isset($dataTypes[$table]['id']) ? $dataTypes[$table]['id'] : null,
            ];
            // return collection
            return (object) $table;
        }, $this->listCollectionNames($Firestore));

        return view('VoyagerFirestore::tools.fbread.index')->with(compact('dataTypes', 'tables'));

        /////////////////////////////////////////////////////////////////////

    }

    public function listCollectionNames(FirestoreClient $Firestore)
    {
        $collectionReference = $Firestore->collections();
        $tables = [];
        foreach ($collectionReference as $collection) {
            $parts = explode('/', $collection->name());
            $tables[] = $parts[count($parts) - 1];
        }
        return $tables;
    }

    public function create(Request $request, $table, FirestoreClient $Firestore)
    {

        Voyager::canOrFail('browse_bread');

        $dataType = Voyager::model('DataType')->whereName($table)->first();

        $data = $this->prepopulateBreadInfo($table);
        $data['fieldOptions'] = $this->describeCollection($Firestore, (isset($dataType) && strlen($dataType->model_name) != 0)
            ? app($dataType->model_name)->getTable()
            : $table
        );
        return view('VoyagerFirestore::tools.fbread.edit-add', $data);
    }

    private function prepopulateBreadInfo($table)
    {
        $displayName = Str::singular(implode(' ', explode('_', Str::title($table))));
        $modelNamespace = config('voyager.models.namespace', app()->getNamespace());
        if (empty($modelNamespace)) {
            $modelNamespace = app()->getNamespace();
        }

        return [
            'isModelTranslatable' => true,
            'table' => $table,
            'slug' => Str::slug($table),
            'display_name' => $displayName,
            'display_name_plural' => Str::plural($displayName),
            'model_name' => $modelNamespace . Str::studly(Str::singular($table)),
            'generate_permissions' => true,
            'server_side' => false,
        ];
    }

    public function describeCollection($Firestore, $collectionName)
    {
        $collectionReference = $Firestore->collection($collectionName);
        $documentReference = $collectionReference->document('structure');
        return collect($documentReference->snapshot()->data());

    }

    public function store(FirestoreClient $Firestore, Request $request)
    {
        try {
            $dataType = Voyager::model('DataType');
            $res = $dataType->FSupdateDataType($Firestore, $request->all(), true);
            $data = $res
            ? $this->alertSuccess(__('voyager::bread.success_created_bread'))
            : $this->alertError(__('voyager::bread.error_creating_bread'));
            if ($res) {
                event(new BreadAdded($dataType, $data));
            }

            return redirect()->route('VoyagerFirestore::tools.fbread.index')->with($data);
        } catch (Exception $e) {
            return redirect()->route('VoyagerFirestore::tools.fbread.index')->with($this->alertException($e, 'Saving Failed'));
        }
    }

    public function edit(FirestoreClient $Firestore, $table)
    {
        Voyager::canOrFail('browse_bread');

        $dataType = Voyager::model('DataType')->whereName($table)->first();

        $fieldOptions = $this->describeCollection($Firestore, (strlen($dataType->model_name) != 0)
            ? app($dataType->model_name)->getTable()
            : $dataType->name
        );

        $isModelTranslatable = is_bread_translatable($dataType);
        $tables = $this->listCollectionNames($Firestore);

        return Voyager::view('VoyagerFirestore::tools.fbread.edit-add', compact('dataType', 'fieldOptions', 'isModelTranslatable', 'tables'));
    }

    public function update(Request $request, $id, FirestoreClient $Firestore)
    {
        Voyager::canOrFail('browse_bread');

        /* @var \TCG\Voyager\Models\DataType $dataType */
        try {
            $dataType = Voyager::model('DataType')->find($id);

            // Prepare Translations and Transform data
            $translations = is_bread_translatable($dataType)
            ? $dataType->prepareTranslations($request)
            : [];

            $res = $dataType->FSupdateDataType($Firestore, $request->all(), true);
            $data = $res
            ? $this->alertSuccess(__('voyager::bread.success_update_bread', ['datatype' => $dataType->name]))
            : $this->alertError(__('voyager::bread.error_updating_bread'));
            if ($res) {
                event(new BreadUpdated($dataType, $data));
            }

            // Save translations if applied
            $dataType->saveTranslations($translations);

            return redirect()->route('VoyagerFirestore.index')->with($data);
        } catch (Exception $e) {
            return back()->with($this->alertException($e, __('voyager::generic.update_failed')));
        }
    }

    public function destroy($id)
    {
        Voyager::canOrFail('browse_bread');

        /* @var \TCG\Voyager\Models\DataType $dataType */
        $dataType = Voyager::model('DataType')->find($id);

        // Delete Translations, if present
        if (is_bread_translatable($dataType)) {
            $dataType->deleteAttributeTranslations($dataType->getTranslatableAttributes());
        }

        $res = Voyager::model('DataType')->destroy($id);
        $data = $res
        ? $this->alertSuccess(__('voyager::bread.success_remove_bread', ['datatype' => $dataType->name]))
        : $this->alertError(__('voyager::bread.error_updating_bread'));
        if ($res) {
            event(new BreadDeleted($dataType, $data));
        }

        if (!is_null($dataType)) {
            Voyager::model('Permission')->removeFrom($dataType->name);
        }

        return redirect()->route('VoyagerFirestore::tools.fbread.index')->with($data);
    }

    protected function alert($message, $type)
    {
        $this->alerts['alerts'][] = [
            'type' => $type,
            'message' => $message,
        ];

        return $this->alerts;
    }

    public function alertSuccess($message)
    {
        return $this->alert($message, 'success');
    }
    public function alertError($message)
    {
        return $this->alert($message, 'error');
    }
}
