<?php


namespace App\Http\Controllers;


use App\Facades\Responder;
use Exception;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Repositories\Src\BaseRepository;


/**
 * Trait TraitBaseController
 * @package App\Traits
 *
 * @property BaseRepository $repository
 * @property $resource
 * @property $request
 */
trait BaseControllerTrait
{
    use ValidatesRequests;

    protected ?BaseRepository $repository;

    protected $resource;

    protected $request;

    protected bool $paginate = false;

    /**
     * Display a listing of the resource.
     * @return mixed
     */
    public function index()
    {
        if ($this->paginate) {
            $data = $this->repository->paginate();
            $data = [
                'data'     => $this->resource::collection($data->items()),
                'per_page' => $data->perPage(),
                'total'    => $data->total(),
            ];
        } else {
            $data = $this->resource::collection($this->repository->all());
        }

        return Responder::respond($data);
    }

    /**
     * Show the specified resource.
     * @param $modelId
     * @return mixed
     */
    public function show($modelId)
    {
        $data = $this->repository->find($modelId);

        return Responder::respond(new $this->resource($data));
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Exception|Application|ResponseFactory|Response
     */
    public function store(Request $request)
    {
        if (isset($this->request)) {
            app($this->request);
        }
        $data = $this->repository->create($request->toArray());

        return $data ? Responder::created(new $this->resource($data)) : Responder::badRequest();
    }

    /**
     * Update the specified resource in storage.
     * @param $modelId
     * @param Request $request
     * @return mixed
     */
    public function update($modelId, Request $request)
    {
        if (isset($this->request)) {
            app($this->request);
        }
        $data = $this->repository->updateById($modelId, $request->toArray());

        return $data ? Responder::updated(new $this->resource($data)) : Responder::badRequest();
    }

    /**
     * @param string|int $modelId
     * @param Request $request
     * @return Application|ResponseFactory|Response|mixed
     * @throws Exception
     */
    public function destroy($modelId, Request $request)
    {
        if ($modelId == 0) {
            $modelId = $request->toArray()['ids'];
            $delete = $this->repository->deleteMultipleById($modelId);
        } else {
            $delete = $this->repository->deleteById($modelId);
        }

        return $delete ? Responder::deleted() : Responder::badRequest();
    }
}
