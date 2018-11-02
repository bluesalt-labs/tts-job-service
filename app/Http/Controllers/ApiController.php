<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ApiController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Index page. Shows the application name.
     *
     * @return string
     */
    public function index() {
        return env('APP_NAME', 'Laravel Lumen');
    }

    /**
     * Submit a new job request.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function submitJobRequest(Request $request) {
        $output = [
            "text"      => $request->get('text'),
            "voices"    => $request->get('voices'),
        ]; // todo

        return response()->json($output);
    }

    /**
     * Get the status of a job by jobID
     *
     * @param $jobID
     * @return \Illuminate\Http\JsonResponse
     */
    public function getJobStatus($jobID) {
        $output = [
            'job_id'    => $jobID
        ]; // todo



        return response()->json($output);
    }

}
