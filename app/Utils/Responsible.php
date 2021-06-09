<?php

namespace App\Utils;


use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Response as Res;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * Trait Responsible
 *
 * @package App\Http\Traits
 */
trait Responsible
{
    /**
     * @var $statusCode int
     */
    protected ?int $statusCode = Res::HTTP_OK;

    /**
     * @var mixed|null
     */
    protected $data = null;

    /**
     * @var mixed|null
     */
    protected $error = null;


    /**
     * @param array|null $data
     * @return Application|ResponseFactory|Res
     */
    public function respond($data = null)
    {
        return \response($data ?? $this->data ?? $this->error, $this->statusCode);
    }


    /**
     * @param $data
     * @return $this
     */
    public function setData($data)
    {
        $this->data = $data;

        return $this;
    }


    /**
     * @param array $data
     * @return $this
     */
    public function appendData(array $data)
    {
        $this->setData(array_merge((array)$this->data, $data));

        return $this;
    }


    /**
     * @param $error
     * @return $this
     */
    public function setError($error)
    {
        $this->error = $error;

        return $this;
    }

    /**
     * @param $statusCode
     * @return $this
     */
    public function setStatusCode($statusCode)
    {
        $this->statusCode = $statusCode;

        return $this;
    }


    public function setMessage(string $message)
    {
        $this->data = ["message" => $message];
        return $this;
    }


    /**
     * @param null $data
     * @return Application|ResponseFactory|Res
     */
    public function created($data = null)
    {
        return $this->setStatusCode(Res::HTTP_CREATED)
            ->respond($data);
    }


    /**
     * @param null $data
     * @return Application|ResponseFactory|Res
     */
    public function updated($data = null)
    {
        return $this->respond($data);
    }


    /**
     * @return Application|ResponseFactory|Res
     */
    public function deleted()
    {
        return $this->respond();
    }


    /**
     * @return Application|ResponseFactory|Res
     */
    public function badRequest()
    {
        if (!$this->data)
            $this->setMessage("bad request!");
        return $this->setStatusCode(Res::HTTP_BAD_REQUEST)
            ->respond();
    }


    /**
     * @return Application|ResponseFactory|Res
     */
    public function notFound()
    {
        return $this->setStatusCode(Res::HTTP_NOT_FOUND)
            ->setMessage("not found!")
            ->respond();
    }


    /**
     * @param null $exception
     * @return Application|ResponseFactory|Res
     */
    public function internalError($exception = null)
    {
        if (!is_null($exception))
            Log::error($exception);

        return $this->setStatusCode(Res::HTTP_INTERNAL_SERVER_ERROR)
            ->setMessage("internal error!")
            ->respond();
    }


    /**
     * @param array $errors
     *
     * @throws ValidationException
     */
    public function validationError($errors = [])
    {
        throw ValidationException::withMessages($errors);
    }


    /**
     * @return Application|ResponseFactory|Res
     */
    public function unauthorizedError()
    {
        return $this->setStatusCode(Res::HTTP_UNAUTHORIZED)
            ->setMessage("unauthorized error!")
            ->respond();
    }


    /**
     * @return Application|ResponseFactory|Res
     */
    public function toManyRequest()
    {
        if (!$this->data)
            $this->setMessage("to many request!");
        return $this->setStatusCode(Res::HTTP_TOO_MANY_REQUESTS)
            ->respond();
    }
}
