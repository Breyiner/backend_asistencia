<?php

namespace App\Helpers;

class ResponseFormatter
{
    /**
     * Create a new class instance.
     */
    public static function success($message = "Operación exitosa", $status = 200, $data, $paginate = []) {

      return response()->json([
        "success"=> true,
        "code" => $status,
        "message"=> $message,
        "data" => $data,
        "paginate" => $paginate
      ], $status);

    }

    public static function error($message, $status, $errors = [], $errorKey = null) {

      return response()->json([
        "success"=> false,
        "code" => $status,
        "message"=> $message,
        "errors" => $errors,
        "errorKey" => $errorKey
      ], $status);

    }
}