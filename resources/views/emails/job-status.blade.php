@component('mail::message')
<h1>Job Status</h1>

<p><strong>Job Type:</strong> {{ $jobType }}</p>

@if($file)
<p><strong>File:</strong> {{ $file }}</p>
@endif

<p><strong>Status:</strong> {{ $status }}</p>

@if($error)
<p><strong>Error:</strong> {{ $error }}</p>
@endif

@if($rowCount)
<p><strong>Row Count:</strong> {{ $rowCount }}</p>
@endif

@if($successCount)
<p><strong>Successful Count:</strong> {{ $successCount }}</p>
@endif

@if($failCount)
<p><strong>Failed Count:</strong> {{ $failCount }}</p>
@endif

@if($newUsers)
<p><strong>Please check the default settings for the following NEW agents:</strong></p>
@foreach ($newUsers as $newUser)
<p>{{ $newUser }}</p>
@endforeach
@endif

@if($updatedUsers)
<p><strong>Please check the default settings for the following RETURNING agents:</strong></p>
@foreach ($updatedUsers as $updatedUser)
<p>{{ $updatedUser }}</p>
@endforeach
@endif

@endcomponent
