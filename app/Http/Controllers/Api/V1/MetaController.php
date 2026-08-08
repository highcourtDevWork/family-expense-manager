<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\NamedResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MetaController extends Controller
{
    /**
     * The family's expense categories, for populating a category picker.
     */
    public function categories(Request $request): AnonymousResourceCollection
    {
        return NamedResource::collection(
            $request->user()->currentFamily()->categories()->orderBy('name')->get()
        );
    }

    /**
     * The family's income sources.
     */
    public function incomeSources(Request $request): AnonymousResourceCollection
    {
        return NamedResource::collection(
            $request->user()->currentFamily()->incomeSources()->orderBy('name')->get()
        );
    }
}
