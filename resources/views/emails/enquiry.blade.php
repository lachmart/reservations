<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <title>Nová poptávka z webu</title>
</head>
<body style="font-family: Arial, sans-serif; color:#0f172a;">
<h2>Nová poptávka z webu</h2>
<table cellpadding="4" style="border-collapse:collapse;">
    <tr><td><strong>Jméno</strong></td><td>{{ $enquiry['name'] }}</td></tr>
    @if(!empty($enquiry['phone']))
        <tr><td><strong>Telefon</strong></td><td><a href="tel:{{ preg_replace('/[^0-9+]/', '', $enquiry['phone']) }}">{{ $enquiry['phone'] }}</a></td></tr>
    @endif
    @if(!empty($enquiry['email']))
        <tr><td><strong>E-mail</strong></td><td><a href="mailto:{{ $enquiry['email'] }}">{{ $enquiry['email'] }}</a></td></tr>
    @endif
    @if(!empty($enquiry['event_date']))
        <tr><td><strong>Datum akce</strong></td><td>{{ \Illuminate\Support\Carbon::parse($enquiry['event_date'])->format('d.m.Y') }}</td></tr>
    @endif
    @if(!empty($enquiry['event_type']))
        <tr><td><strong>Typ akce</strong></td><td>{{ $enquiry['event_type'] }}</td></tr>
    @endif
</table>
<h3>Zpráva</h3>
<p style="white-space:pre-line;">{{ $enquiry['message'] }}</p>
@if(!empty($enquiry['page']))
    <p style="color:#64748b;font-size:12px;">Odesláno ze stránky: {{ $enquiry['page'] }}</p>
@endif
</body>
</html>
