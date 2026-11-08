<?php

namespace App\Http\Controllers;

/**
 * Base class for every controller.
 *
 * Controllers stay thin: validation happens in FormRequest classes (app/Http/Requests),
 * business rules in services (app/Services), permissions in middleware and policies,
 * and the JSON shape in Resource classes (app/Http/Resources).
 */
abstract class Controller
{
    //
}
