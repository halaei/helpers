<?php

use Illuminate\Http\JsonResponse;

/**
 * Wrap the success result in a JSON response.
 *
 * @param mixed $result
 *
 * @return JsonResponse
 */
function api_success($result)
{
    return response()->json([
        'status' => 'OK',
        'result'  => $result,
    ], 200, [], JsonResponse::DEFAULT_ENCODING_OPTIONS | JSON_UNESCAPED_UNICODE);
}

/**
 * The JSON API error response.
 *
 * @param int $statusCode
 * @param null|string $errorCode
 * @param null|string $message
 * @param array $info
 *
 * @return JsonResponse
 */
function api_error($statusCode, $errorCode = null, $message = null, array $info = [])
{
    return response()->json(
        api_error_body($statusCode, $errorCode, $message, $info),
        $statusCode,
        [],
        JsonResponse::DEFAULT_ENCODING_OPTIONS | JSON_UNESCAPED_UNICODE);
}

/**
 * The body of API error response in array format.
 *
 * @param int $statusCode
 * @param null|string $errorCode
 * @param null|string $message
 * @param array $info
 *
 * @return array
 */
function api_error_body($statusCode, $errorCode, $message, array $info)
{
    if (is_null($message)) {
        if (trans('api_errors.'.$statusCode) === 'api_errors.'.$statusCode) {
            $message = $statusCode >= 500 ? trans('api_errors.500') : trans('api_errors.400');
        } else {
            $message = trans('api_errors.'.$statusCode);
        }
    }

    $body = [
        'status' => 'ERROR',
        'error' => [
            'code' => $errorCode,
            'message' => $message,
        ],
    ];

    if ($info) {
        $body['error']['info'] = $info;
    }

    return $body;
}
