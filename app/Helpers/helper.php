<?php

namespace App\Helpers;

use Carbon\Carbon;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class Helper
{
    public static function formatDate($date, $format = 'Y-m-d')
    {
        return Carbon::parse($date)->format($format);
    }

    public static function formatTime($time, $format = 'H:i:s')
    {
        return Carbon::parse($time)->format($format);
    }

    public static function formatDateTime($dateTime, $format = 'd/m/Y H:i:s')
    {
        return Carbon::parse($dateTime)->format($format);
    }

    public static function convertDateTz($date, $from_tz = 'UTC', $to_tz = 'Asia/Jakarta')
    {
        return Carbon::createFromDate($date, $from_tz)->timezone($to_tz);
    }

    public static function paginateInertiaResources(Paginator $resource): array
    {
        return [
            'currentPage' => $resource->currentPage(),
            'data' => $resource->items(),
            'firstPageUrl' => $resource->url(1),
            'from' => $resource->firstItem(),
            'lastPage' => $resource->lastPage(),
            'lastPageUrl' => $resource->url($resource->lastPage()),
            'links' => $resource->links(null, $resource->items()),
            'nextPageUrl' => $resource->nextPageUrl(),
            'path' => $resource->path(),
            'perPage' => $resource->perPage(),
            'prevPageUrl' => $resource->previousPageUrl(),
            'to' => $resource->lastItem(),
            'total' => $resource->total(),
        ];
    }

    public static function responseJson(object|array $data = [], string $status = 'success', int $statusCode = 200, string $message = 'Data berhasil di ambil'): JsonResponse
    {
        return response()->json([
            'status' => $status,
            'statusCode' => $statusCode,
            'message' => $message,
            'data' => $data,
        ], $statusCode);
    }
}
