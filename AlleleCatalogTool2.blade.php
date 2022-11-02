@php
include resource_path() . '/views/system/config.blade.php';

$organism = $info['organism'];
$dataset_array = $info['dataset_array'];
$gene_array = $info['gene_array'];
$accession_array = $info['accession_array'];
$key_column = $info['key_column'];
$improvement_status_array = $info['improvement_status_array'];
$accession_mapping_table = $info['accession_mapping_table'];

@endphp


@extends('system.header')


@section('content')

<div class="title1">Allele Catalog Tool</div>
<br />

<table width="100%" cellspacing="14" cellpadding="14">
    <tr>
        <td width="50%" align="center" valign="top" style="border:1px solid #999999; padding:10px; background-color:#f8f8f8; text-align:left;">
            <form action="{{ route('system.tools.AlleleCatalogTool2.viewAllByGenes', ['organism'=>$organism]) }}" method="get" target="_blank">
                <h2>Search by Gene IDs</h2>
                <br />
                <label for="dataset_1"><b>Dataset:</b></label>
                <select name="dataset_1" id="dataset_1">
                    @foreach($dataset_array as $dataset)
                    <option value="{{ $dataset }}">{{ str_replace('_', ' ', $dataset) . " Allele Catalog" }}</option>
                    @endforeach
                </select>
                <br />
                <br />
                <b>Gene IDs:</b><br />
                <span style="font-size:10pt">
                    &nbsp;(eg
                    @foreach($gene_array as $gene)
                    {{ $gene->Gene }}
                    @endforeach
                    )
                </span>
                <br />
                <textarea id="gene_1" name="gene_1" rows="12" cols="40"></textarea>
                <br /><br />
                <b>{{ str_replace('_', ' ', $key_column) }}:</b><br />
                @foreach($improvement_status_array as $key => $improvement_status)
                <input type="checkbox" id="{{ $improvement_status->Key }}" name="improvement_status_1[]" value="{{ $improvement_status->Key }}" checked>
                <label for="{{ $improvement_status->Key }}" style="font-weight: normal;">{{ str_replace('_', ' ', $improvement_status->Key) }}</label>
                @if ($key != 0 && $key % 4 === 0)
                    <br />
                @endif
                @endforeach
                <br /><br />
                <input type="submit" value="Search">
            </form>
        </td>
        <td width="50%" align="center" valign="top" style="border:1px solid #999999; padding:10px; background-color:#f8f8f8; text-align:left;">
            <form action="{{ route('system.tools.AlleleCatalogTool2.viewAllByAccessionsAndGene', ['organism'=>$organism]) }}" method="get" target="_blank">
                <h2>Search by Accessions and Gene ID</h2>
                <br />
                <label for="dataset_2"><b>Dataset:</b></label>
                <select name="dataset_2" id="dataset_2">
                    @foreach($dataset_array as $dataset)
                    <option value="{{ $dataset }}">{{ str_replace('_', ' ', $dataset) . " Allele Catalog" }}</option>
                    @endforeach
                </select>
                <br />
                <br />
                <b>Accessions:</b>
                <span style="font-size:10pt">
                    &nbsp;(eg
                    @foreach($accession_array as $accession)
                    {{ $accession->Accession }}
                    @endforeach
                    )
                </span>
                <br />
                <textarea id="accession_2" name="accession_2" rows="12" cols="40"></textarea>
                <br /><br />
                <b>Gene ID:</b><span style="font-size:10pt">&nbsp;(One gene name only; eg {{ $gene_array[0]->Gene }})</span>
                <br />
                <input type="text" id="gene_2" name="gene_2" size="40"></input>
                <br /><br />
                <br /><br />
                <input type="submit" value="Search">
            </form>
        </td>
    </tr>
</table>


<br />
<br />
<div style='margin-top:10px;' align='center'>
    <button type="submit" onclick="queryAccessionInformation('{{ $organism }}', '{{ $accession_mapping_table }}')" style="margin-right:20px;">Download Accession Information</button>
</div>

@endsection


@section('javascript')

<script src="{{ asset('system/home/AlleleCatalogTool2/js/AlleleCatalogTool2.js') }}" type="text/javascript"></script>

<script type="text/javascript">
    // Populate gene1 textarea placeholder
    let gene_array = <?php echo json_encode($gene_array); ?>;
    gene1_placeholder = "\nPlease separate each gene into a new line.\n\nExample:\n";
    for (let i = 0; i < gene_array.length; i++) {
        gene1_placeholder += gene_array[i]['Gene'] + "\n";
    }
    document.getElementById('gene_1').placeholder = gene1_placeholder;


    // Populate accession textarea placeholder
    let accession_array = <?php echo json_encode($accession_array); ?>;
    accession_placeholder = "\nPlease separate each accession into a new line.\n\nExample:\n";
    for (let i = 0; i < accession_array.length; i++) {
        accession_placeholder += accession_array[i]['Accession'] + "\n";
    }
    document.getElementById('accession_2').placeholder = accession_placeholder;
</script>

@endsection