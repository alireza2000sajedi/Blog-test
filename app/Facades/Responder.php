<?php

namespace App\Facades;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\Facade;
use App\Utils\Responder as ResponderUtil;
use Illuminate\Validation\ValidationException;

/**
 * Class Responder
 * @package Enibo\Base\App\Facades
 *
 * @method static Response respond($data = [])
 * @method static ResponderUtil setData($data)
 * @method static ResponderUtil setMessage(string $message)
 * @method static ResponderUtil appendData($data)
 * @method static ResponderUtil setError($error)
 * @method static ResponderUtil setStatusCode($statusCode)
 * @method static created($data = null)
 * @method static updated($data = null)
 * @method static deleted()
 * @method static Response badRequest()
 * @method static Response toManyRequest()
 * @method static notFound()
 * @method static internalError($exception = null)
 * @method static ValidationException validationError($errors = [])
 * @method static unAuthorizedError()
 */
class Responder extends Facade
{
    protected static function getFacadeAccessor()
    {
        return ResponderUtil::class;
    }
}
