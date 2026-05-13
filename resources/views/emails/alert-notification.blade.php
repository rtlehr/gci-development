@if ($ticket)

    <h2>
        A new ticket has been submitted, {{ $ticket->ticket_number }}
    </h2>

    <p>
        Please review the following ticket.
    </p>

    <p>
        <strong>Ticket Subject:</strong><br>
        {{ $ticket->title }}
    </p>

    <p>
        <strong>Request:</strong><br>
        {!! nl2br(e($ticket->description)) !!}
    </p>

    @if ($alert->action_url)
        <p>
            <a href="{{ url($alert->action_url) }}">
                View Ticket Information
            </a>
        </p>
    @endif

@else

    <h2>{{ $alert->title }}</h2>

    @if ($alert->message)
        <p>{{ $alert->message }}</p>
    @endif

    @if ($alert->action_url)
        <p>
            <a href="{{ url($alert->action_url) }}">
                View Item
            </a>
        </p>
    @endif

@endif