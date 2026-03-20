@extends('layouts.app')

@section('content')
<style>
    .record-card { font-size: 0.85rem; }
    .record-card .card-header { font-weight: 600; font-size: 0.8rem; background: #f8f9fa; padding: 6px 10px; }
    .record-card table td, .record-card table th { padding: 3px 8px; }
    .record-card .rank { color: #6c757d; width: 20px; }
    .record-card .extra { color: #6c757d; font-size: 0.75rem; }
    .accordion-button { font-weight: 600; }
</style>

<div class="container">
    <h1 class="mb-4">Records</h1>

    @if(!$records)
        <div class="card">
            <div class="card-body">
                <p class="text-muted mb-0">No results found for this world.</p>
            </div>
        </div>
    @else

    @php
    $sections = [
        'entries' => [
            'label'   => 'Entries',
            'records' => [
                'total'               => 'Total Entries',
                'youngest'            => 'Youngest Entrant',
                'oldest'              => 'Oldest Entrant',
                'consecutive_entries' => 'Consecutive Entries',
                'consecutive_starts'  => 'Consecutive Starts',
                'one_constructor'     => 'With One Constructor',
            ],
        ],
        'wins' => [
            'label'   => 'Wins',
            'records' => [
                'total'             => 'Total Wins',
                'percentage'        => 'Win Percentage',
                'single_constructor'=> 'With One Constructor',
                'in_season'         => 'Wins in a Season',
                'pct_in_season'     => 'Win % in a Season',
                'consecutive'       => 'Consecutive Wins',
                'first_season'      => 'Wins in First Season',
                'youngest'          => 'Youngest Winner',
                'oldest'            => 'Oldest Winner',
                'races_before_win'  => 'Races Before First Win',
                'without_win'       => 'Most Races Without a Win',
                'at_same_gp'        => 'Wins at Same GP',
            ],
        ],
        'poles' => [
            'label'   => 'Pole Positions',
            'records' => [
                'total'         => 'Total Poles',
                'percentage'    => 'Pole Percentage',
                'in_season'     => 'Poles in a Season',
                'pct_in_season' => 'Pole % in a Season',
                'consecutive'   => 'Consecutive Poles',
                'youngest'      => 'Youngest Pole',
                'oldest'        => 'Oldest Pole',
                'at_same_gp'    => 'Poles at Same GP',
            ],
        ],
        'fastest_laps' => [
            'label'   => 'Fastest Laps',
            'records' => [
                'total'         => 'Total Fastest Laps',
                'percentage'    => 'Fastest Lap %',
                'in_season'     => 'Fastest Laps in a Season',
                'pct_in_season' => 'Fastest Lap % in a Season',
                'consecutive'   => 'Consecutive Fastest Laps',
                'youngest'      => 'Youngest Fastest Lap',
                'oldest'        => 'Oldest Fastest Lap',
                'at_same_gp'    => 'Fastest Laps at Same GP',
            ],
        ],
        'podiums' => [
            'label'   => 'Podiums',
            'records' => [
                'total'         => 'Total Podiums',
                'percentage'    => 'Podium Percentage',
                'in_season'     => 'Podiums in a Season',
                'pct_in_season' => 'Podium % in a Season',
                'consecutive'   => 'Consecutive Podiums',
                'youngest'      => 'Youngest Podium',
                'oldest'        => 'Oldest Podium',
                'at_same_gp'    => 'Podiums at Same GP',
            ],
        ],
        'points' => [
            'label'   => 'Points',
            'records' => [
                'total'         => 'Total Points',
                'percentage'    => 'Points Scoring %',
                'in_season'     => 'Points in a Season',
                'pct_in_season' => 'Points Scoring % in a Season',
                'consecutive'   => 'Consecutive Points Finishes',
                'youngest'      => 'Youngest Points Finish',
                'oldest'        => 'Oldest Points Finish',
                'at_same_gp'    => 'Most Points at Same GP',
            ],
        ],
        'race_finishes' => [
            'label'   => 'Race Finishes',
            'records' => [
                'total'       => 'Total Finishes',
                'consecutive' => 'Consecutive Finishes',
            ],
        ],
        'championships' => [
            'label'   => 'Drivers Championships',
            'records' => [
                'total'    => 'Most Championships',
                'youngest' => 'Youngest Champion',
                'oldest'   => 'Oldest Champion',
            ],
        ],
    ];
    @endphp

    <div class="accordion" id="recordsAccordion">
        @foreach($sections as $sectionKey => $section)
        @php $sectionData = $records[$sectionKey] ?? []; @endphp
        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button {{ !$loop->first ? 'collapsed' : '' }}"
                        type="button"
                        data-bs-toggle="collapse"
                        data-bs-target="#section-{{ $sectionKey }}">
                    {{ $section['label'] }}
                </button>
            </h2>
            <div id="section-{{ $sectionKey }}"
                 class="accordion-collapse collapse {{ $loop->first ? 'show' : '' }}"
                 data-bs-parent="#recordsAccordion">
                <div class="accordion-body">
                    <div class="row g-3">
                        @foreach($section['records'] as $recordKey => $recordLabel)
                        @php $rows = $sectionData[$recordKey] ?? []; @endphp
                        <div class="col-md-4 col-lg-3">
                            <div class="card record-card h-100">
                                <div class="card-header">{{ $recordLabel }}</div>
                                @if(empty($rows))
                                    <div class="card-body py-2">
                                        <span class="text-muted" style="font-size:0.75rem">No data</span>
                                    </div>
                                @else
                                    <table class="table table-sm mb-0">
                                        <tbody>
                                            @foreach($rows as $i => $row)
                                            <tr>
                                                <td class="rank">{{ $i + 1 }}</td>
                                                <td>
                                                    {{ $row['name'] }}
                                                    @if(!empty($row['extra']))
                                                        <span class="extra d-block">{{ $row['extra'] }}</span>
                                                    @endif
                                                </td>
                                                <td class="text-end fw-semibold">{{ $row['value'] }}</td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    @endif
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
@endsection
