<!-- Tabela de Frequência Mensal -->
<div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
    <table class="table table-bordered table-sm">
        <thead style="position: sticky; top: 0; background: white; z-index: 10;">
            <tr>
                <th rowspan="2" style="min-width: 200px; vertical-align: middle;">Nome</th>
                <th colspan="{{ $daysInMonth }}" class="text-center">
                    {{ \Carbon\Carbon::create($year, $month, 1)->translatedFormat('F') }} - {{ $year }}
                </th>
            </tr>
            <tr>
                @for($day = 1; $day <= $daysInMonth; $day++)
                    <th class="text-center" style="min-width: 30px;">{{ $day }}</th>
                @endfor
            </tr>
        </thead>
        <tbody>
            @foreach($students as $student)
                <tr>
                    <td><strong>{{ $student->name }}</strong></td>
                    @for($day = 1; $day <= $daysInMonth; $day++)
                        @php
                            $date = \Carbon\Carbon::create($year, $month, $day);
                            $attendance = $lessons->flatMap(function($lesson) use ($student, $date) {
                                return $lesson->attendances->filter(function($att) use ($student, $date, $lesson) {
                                    return $att->member_id == $student->id && 
                                           $lesson->lesson_date->format('Y-m-d') == $date->format('Y-m-d') &&
                                           $att->present;
                                });
                            })->first();
                        @endphp
                        <td class="text-center" style="padding: 5px;">
                            @if($attendance)
                                <span class="badge bg-success">P</span>
                            @elseif($lessons->contains(function($lesson) use ($date) {
                                return $lesson->lesson_date->format('Y-m-d') == $date->format('Y-m-d');
                            }))
                                <span class="badge bg-danger">F</span>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                    @endfor
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

