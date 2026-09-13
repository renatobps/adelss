@php
    $intercessionGroups = $section->groupedIntercessionVolunteers($volunteers);
@endphp
@foreach($intercessionGroups as $period => $positions)
    <div class="period-title">{{ $period }}</div>
    <table class="volunteers">
        @foreach($positions as $position => $names)
            <tr>
                <td class="volunteer-name">
                    {{ $position }} — {{ implode(' e ', $names) }}
                </td>
            </tr>
        @endforeach
    </table>
@endforeach
