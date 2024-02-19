@component('mail::message')
<h1>Document Upload</h1>

<p><strong>Agent:</strong> {{ $document->documentable->agent_name }} ({{ $document->documentable->id }})</p>

<p><strong>Document Type:</strong> {{ $document->documentType->name }}</p>

<p><strong>Document Name:</strong> <a href="{{ $document->getTemporaryDownloadUrl(now()->addDays(7)) }}" target="_blank">{{ $document->title }}</a></p>

<p><em>The download link above will expire in 7 days. The document will continue to be available in the portal after the link expires.</em></p>

@endcomponent
