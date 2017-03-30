<?php
/*
 * This is an unofficial snippet, with no support! You may copy-paste and use at your risk.
 */

/**
 * Wrap the success result in a JSON response.
 *
 * @param mixed $result
 *
 * @return \Illuminate\Http\JsonResponse
 */
function api_success($result)
{
    return response()->json([
        'success' => true,
        'result'  => $result,
    ]);
}

/**
 * The JSON API error response.
 *
 * @param int $statusCode
 * @param null|string $message
 * @param array $errors
 *
 * @return \Illuminate\Http\JsonResponse
 */
function api_error($statusCode, $message = null, array $errors = [])
{
    return response()->json(api_error_body($statusCode, $message, $errors), $statusCode);
}

/**
 * The body of API error response in array format.
 *
 * @param int $statusCode
 * @param null|string $message
 * @param array $errors
 *
 * @return array
 */
function api_error_body($statusCode, $message = null, array $errors = [])
{
    if (is_null($message)) {
        if (trans('api_errors.'.$statusCode) === 'api_errors.'.$statusCode) {
            $message = $statusCode >= 500 ? trans('api_errors.500') : trans('api_errors.400');
        } else {
            $message = trans('api_errors.'.$statusCode);
        }
    }

    $body = [
        'success' => false,
        'message' => $message,
    ];

    if ($errors) {
        $body['errors'] = $errors;
    }

    return $body;
}
