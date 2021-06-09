<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Repositories\Contracts\PostRepositoryInterface;
use App\Http\Requests\PostRequest;
use App\Http\Resources\PostResource;
use App\Facades\Responder;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class PostController extends Controller
{
    use BaseControllerTrait;

    public function __construct(PostRepositoryInterface $repository)
    {
        $this->middleware('can:create post')->only(['update', 'store']);
        Auth::loginUsingId(1);
        $this->paginate = true;
        $this->repository = $repository;
        $this->request = PostRequest::class;
        $this->resource = PostResource::class;
    }

    public function store(Request $request): Response
    {
        if (isset($this->request)) {
            app($this->request);
        }
        $data = array_merge($request->toArray(), ['created_by' => auth()->id()]);
        $data = $this->repository->create($data);

        return $data ? Responder::created(new $this->resource($data)) : Responder::badRequest();
    }
}
