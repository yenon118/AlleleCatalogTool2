<?php

namespace App\Http\Controllers\System\Tools;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

use App\KBCClasses\DBAdminWrapperClass;
use App\KBCClasses\DBKBCWrapperClass;

class KBCToolsAlleleCatalogTool2Controller extends Controller
{


    function __construct()
    {
        $this->db_kbc_wrapper = new DBKBCWrapperClass;
    }


    public function AlleleCatalogTool2Page(Request $request, $organism)
    {
        $admin_db_wapper = new DBAdminWrapperClass;

        // Database
        $db = "KBC_" . $organism;

        // Table names and datasets
        if ($organism == "Zmays") {
            $gff_table = "act_Maize_AGPv3_GFF";
            $accession_mapping_table = "act_Maize1210_Accession_Mapping";
        } elseif ($organism == "Athaliana") {
            $gff_table = "act_Arabidopsis_TAIR10_GFF";
            $accession_mapping_table = "act_Arabidopsis1135_Accession_Mapping";
        } elseif ($organism == "Osativa") {
            $gff_table = "act_Rice_Nipponbare_GFF";
            $accession_mapping_table = "act_Rice3000_Accession_Mapping";
        }

        // Define datasets
        if ($organism == "Zmays") {
            $dataset_array = array("Maize1210");
        } elseif ($organism == "Athaliana") {
            $dataset_array = array("Arabidopsis1135");
        } elseif ($organism == "Osativa") {
            $dataset_array = array("Rice3000");
        }

        try {
            // Query gene from database
            if ($organism == "Zmays") {
                $sql = "SELECT DISTINCT Name AS Gene FROM " . $db . "." . $gff_table . " WHERE Name IS NOT NULL AND Name LIKE 'GRMZM%' LIMIT 3;";
            } else {
                $sql = "SELECT DISTINCT Name AS Gene FROM " . $db . "." . $gff_table . " WHERE Name IS NOT NULL LIMIT 3;";
            }
            $gene_array = DB::connection($db)->select($sql);

            // Query improvement status, group, or subpopulation from database
            if ($organism == "Zmays") {
                $key_column = "Improvement_Status";
                $sql = "SELECT DISTINCT Improvement_Status AS `Key` FROM " . $db . "." . $accession_mapping_table . ";";
            } elseif ($organism == "Athaliana") {
                $key_column = "Group";
                $sql = "SELECT DISTINCT `Group` AS `Key` FROM " . $db . "." . $accession_mapping_table . ";";
            } elseif ($organism == "Osativa") {
                $key_column = "Subpopulation";
                $sql = "SELECT DISTINCT Subpopulation AS `Key` FROM " . $db . "." . $accession_mapping_table . ";";
            }
            $improvement_status_array = DB::connection($db)->select($sql);

            // Query accession from database
            if ($organism == "Zmays") {
                $sql = "SELECT DISTINCT Panzea_Accession AS Accession FROM " . $db . "." . $accession_mapping_table . " WHERE Accession IS NOT NULL LIMIT 3;";
            } elseif ($organism == "Athaliana") {
                $sql = "SELECT DISTINCT TAIR_Accession AS Accession FROM " . $db . "." . $accession_mapping_table . " WHERE Accession IS NOT NULL LIMIT 3;";
            } elseif ($organism == "Osativa") {
                $sql = "SELECT DISTINCT Accession FROM " . $db . "." . $accession_mapping_table . " WHERE Accession IS NOT NULL LIMIT 3;";
            }
            $accession_array = DB::connection($db)->select($sql);

            // Package variables that need to go to the view
            $info = [
                'organism' => $organism,
                'dataset_array' => $dataset_array,
                'gene_array' => $gene_array,
                'accession_array' => $accession_array,
                'key_column' => $key_column,
                'improvement_status_array' => $improvement_status_array,
                'accession_mapping_table' => $accession_mapping_table
            ];

            // Return to view
            return view('system/tools/AlleleCatalogTool2/AlleleCatalogTool2')->with('info', $info);
        } catch (\Exception $e) {
            // Package variables that need to go to the view
            $info = [
                'organism' => $organism
            ];

            // Return to view
            return view('system/tools/AlleleCatalogTool2/AlleleCatalogTool2NotAvailable')->with('info', $info);
        }

    }


    public function QueryAccessionInformation(Request $request, $organism) {

        // Database
        $db = "KBC_" . $organism;

        $dataset = $request->Dataset;

        if ($organism == "Zmays" && $dataset == "Maize1210") {
            $accession_mapping_table = "act_Maize1210_Accession_Mapping";
        } elseif ($organism == "Athaliana" && $dataset == "Arabidopsis1135") {
            $accession_mapping_table = "act_Arabidopsis1135_Accession_Mapping";
        } elseif ($organism == "Osativa" && $dataset == "Rice3000") {
            $accession_mapping_table = "act_Rice3000_Accession_Mapping";
        } else {
            $accession_mapping_table = $dataset;
        }

        // Query string
        $query_str = "SELECT * FROM " . $db . "." . $accession_mapping_table . ";";

        $result_arr = DB::connection($db)->select($query_str);

        return json_encode($result_arr);
    }


    public function ViewAllByGenesPage(Request $request, $organism)
    {
        $admin_db_wapper = new DBAdminWrapperClass;

        // Database
        $db = "KBC_" . $organism;

        $query_str = "SET SESSION group_concat_max_len = 1000000; ";
        $set_group_concat_max_len_result = DB::connection($db)->select($query_str);

        $dataset = $request->dataset_1;
        $gene = $request->gene_1;
        $improvement_status = $request->improvement_status_1;

        if (is_string($gene)) {
            $gene_array = preg_split("/[;, \n]+/", $gene);
            for ($i = 0; $i < count($gene_array); $i++) {
                $gene_array[$i] = trim($gene_array[$i]);
            }
        } elseif (is_array($gene)) {
            $gene_array = $gene;
            for ($i = 0; $i < count($gene_array); $i++) {
                $gene_array[$i] = trim($gene_array[$i]);
            }
        }

        if (is_string($improvement_status)) {
            $improvement_status_array = preg_split("/[;, \n]+/", $improvement_status);
            for ($i = 0; $i < count($improvement_status_array); $i++) {
                $improvement_status_array[$i] = trim($improvement_status_array[$i]);
            }
        } elseif (is_array($improvement_status)) {
            $improvement_status_array = $improvement_status;
            for ($i = 0; $i < count($improvement_status_array); $i++) {
                $improvement_status_array[$i] = trim($improvement_status_array[$i]);
            }
        }

        // Define key column
        if ($organism == "Zmays") {
            $key_column = "Improvement_Status";
        } elseif ($organism == "Athaliana") {
            $key_column = "Group";
        } elseif ($organism == "Osativa") {
            $key_column = "Subpopulation";
        }

        // Table names and datasets
        if ($organism == "Zmays") {
            $gff_table = "act_Maize_AGPv3_GFF";
            $accession_mapping_table = "act_Maize1210_Accession_Mapping";
        } elseif ($organism == "Athaliana") {
            $gff_table = "act_Arabidopsis_TAIR10_GFF";
            $accession_mapping_table = "act_Arabidopsis1135_Accession_Mapping";
        } elseif ($organism == "Osativa") {
            $gff_table = "act_Rice_Nipponbare_GFF";
            $accession_mapping_table = "act_Rice3000_Accession_Mapping";
        }

        $gene_result_arr = Array();
        $allele_catalog_result_arr = Array();

        for ($i = 0; $i < count($gene_array); $i++) {

            try {
                // Generate SQL string
                $query_str = "SELECT Chromosome, Start, End, Name AS Gene ";
                $query_str = $query_str . "FROM " . $db . "." . $gff_table . " ";
                $query_str = $query_str . "WHERE Name IN ('" . $gene_array[$i] . "');";

                $temp_gene_result_arr = DB::connection($db)->select($query_str);

                if ($organism == "Zmays") {
                    // Generate SQL string
                    $query_str = "SELECT ";
                    if (in_array("Improved_Cultivar", $improvement_status_array)) {
                        $query_str = $query_str . "COUNT(IF(ACD.Improvement_Status = 'Improved_Cultivar', 1, null)) AS Improved_Cultivar, ";
                    }
                    if (in_array("Landrace", $improvement_status_array)) {
                        $query_str = $query_str . "COUNT(IF(ACD.Improvement_Status = 'Landrace', 1, null)) AS Landrace, ";
                    }
                    if (in_array("Wild_Relative", $improvement_status_array)) {
                        $query_str = $query_str . "COUNT(IF(ACD.Improvement_Status = 'Wild_Relative', 1, null)) AS Wild_Relative, ";
                    }
                    if (in_array("exPVP", $improvement_status_array)) {
                        $query_str = $query_str . "COUNT(IF(ACD.Improvement_Status = 'exPVP', 1, null)) AS exPVP, ";
                    }
                    if (in_array("Other", $improvement_status_array)) {
                        $query_str = $query_str . "COUNT(IF(ACD.Improvement_Status = 'Other', 1, null)) AS Other, ";
                    }
                    $query_str = $query_str . "COUNT(IF(ACD.Improvement_Status IN ('Improved_Cultivar', 'Landrace', 'Wild_Relative', 'exPVP', 'Other'), 1, null)) AS Total, ";
                    $query_str = $query_str . "ACD.Gene, ACD.Chromosome, ACD.Position, ACD.Genotype, ACD.Genotype_Description ";
                    $query_str = $query_str . "FROM ( ";
                    $query_str = $query_str . "    SELECT AM.Improvement_Status, GD.Accession, ";
                    $query_str = $query_str . "    GD.Gene, GD.Chromosome, ";
                    $query_str = $query_str . "    GROUP_CONCAT(GD.Position SEPARATOR ' ') AS Position, ";
                    $query_str = $query_str . "    GROUP_CONCAT(GD.Genotype SEPARATOR ' ') AS Genotype, ";
                    $query_str = $query_str . "    GROUP_CONCAT(GD.Genotype_Description SEPARATOR ' ') AS Genotype_Description ";
                    $query_str = $query_str . "    FROM ( ";
                    $query_str = $query_str . "        SELECT G.Chromosome, G.Position, G.Accession, GFF.Gene, G.Genotype, ";
                    $query_str = $query_str . "        CONCAT_WS('|', G.Genotype, G.Functional_Effect, G.Amino_Acid_Change) AS Genotype_Description ";
                    $query_str = $query_str . "        FROM ( ";
                    $query_str = $query_str . "            SELECT Chromosome, Start, End, Name AS Gene ";
                    $query_str = $query_str . "            FROM " . $db . "." . $gff_table . " ";
                    $query_str = $query_str . "            WHERE Name IN ('" . $gene_array[$i] . "') ";
                    $query_str = $query_str . "        ) AS GFF ";
                    $query_str = $query_str . "        INNER JOIN " . $db . ".act_" . $dataset . "_" . $temp_gene_result_arr[0]->Chromosome . " AS G ";
                    $query_str = $query_str . "        ON (G.Chromosome = GFF.Chromosome) AND (G.Position >= GFF.Start) AND (G.Position <= GFF.End) ";
                    $query_str = $query_str . "        ORDER BY G.Position ";
                    $query_str = $query_str . "    ) AS GD ";
                    $query_str = $query_str . "    LEFT JOIN " . $db . "." . $accession_mapping_table . " AS AM ";
                    $query_str = $query_str . "    ON AM.Accession = GD.Accession ";
                    $query_str = $query_str . "    GROUP BY AM.Improvement_Status, GD.Accession, GD.Gene, GD.Chromosome ";
                    $query_str = $query_str . ") AS ACD ";
                    $query_str = $query_str . "GROUP BY ACD.Gene, ACD.Chromosome, ACD.Position, ACD.Genotype, ACD.Genotype_Description ";
                    $query_str = $query_str . "ORDER BY ACD.Gene, Total DESC; ";

                } elseif ($organism == "Athaliana") {
                    // Generate SQL string
                    $query_str = "SELECT ";
                    if (in_array("Central", $improvement_status_array)) {
                        $query_str = $query_str . "COUNT(IF(ACD.Group = 'Central', 1, null)) AS Central, ";
                    }
                    if (in_array("East", $improvement_status_array)) {
                        $query_str = $query_str . "COUNT(IF(ACD.Group = 'East', 1, null)) AS East, ";
                    }
                    if (in_array("North", $improvement_status_array)) {
                        $query_str = $query_str . "COUNT(IF(ACD.Group = 'North', 1, null)) AS North, ";
                    }
                    if (in_array("South", $improvement_status_array)) {
                        $query_str = $query_str . "COUNT(IF(ACD.Group = 'South', 1, null)) AS South, ";
                    }
                    if (in_array("Other", $improvement_status_array)) {
                        $query_str = $query_str . "COUNT(IF(ACD.Group = 'Other', 1, null)) AS Other, ";
                    }
                    $query_str = $query_str . "COUNT(IF(ACD.Group IN ('Central', 'East', 'North', 'South', 'Other'), 1, null)) AS Total, ";
                    $query_str = $query_str . "ACD.Gene, ACD.Chromosome, ACD.Position, ACD.Genotype, ACD.Genotype_Description ";
                    $query_str = $query_str . "FROM ( ";
                    $query_str = $query_str . "    SELECT AM.Group, GD.Accession, ";
                    $query_str = $query_str . "    GD.Gene, GD.Chromosome, ";
                    $query_str = $query_str . "    GROUP_CONCAT(GD.Position SEPARATOR ' ') AS Position, ";
                    $query_str = $query_str . "    GROUP_CONCAT(GD.Genotype SEPARATOR ' ') AS Genotype, ";
                    $query_str = $query_str . "    GROUP_CONCAT(GD.Genotype_Description SEPARATOR ' ') AS Genotype_Description ";
                    $query_str = $query_str . "    FROM ( ";
                    $query_str = $query_str . "        SELECT G.Chromosome, G.Position, G.Accession, GFF.Gene, G.Genotype, ";
                    $query_str = $query_str . "        CONCAT_WS('|', G.Genotype, G.Functional_Effect, G.Amino_Acid_Change) AS Genotype_Description ";
                    $query_str = $query_str . "        FROM ( ";
                    $query_str = $query_str . "            SELECT Chromosome, Start, End, Name AS Gene ";
                    $query_str = $query_str . "            FROM " . $db . "." . $gff_table . " ";
                    $query_str = $query_str . "            WHERE Name IN ('" . $gene_array[$i] . "') ";
                    $query_str = $query_str . "        ) AS GFF ";
                    $query_str = $query_str . "        INNER JOIN " . $db . ".act_" . $dataset . "_" . $temp_gene_result_arr[0]->Chromosome . " AS G ";
                    $query_str = $query_str . "        ON (G.Chromosome = GFF.Chromosome) AND (G.Position >= GFF.Start) AND (G.Position <= GFF.End) ";
                    $query_str = $query_str . "        ORDER BY G.Position ";
                    $query_str = $query_str . "    ) AS GD ";
                    $query_str = $query_str . "    LEFT JOIN " . $db . "." . $accession_mapping_table . " AS AM ";
                    $query_str = $query_str . "    ON AM.Accession = GD.Accession ";
                    $query_str = $query_str . "    GROUP BY AM.Group, GD.Accession, GD.Gene, GD.Chromosome ";
                    $query_str = $query_str . ") AS ACD ";
                    $query_str = $query_str . "GROUP BY ACD.Gene, ACD.Chromosome, ACD.Position, ACD.Genotype, ACD.Genotype_Description ";
                    $query_str = $query_str . "ORDER BY ACD.Gene, Total DESC; ";
                } elseif ($organism == "Osativa") {
                    // Generate SQL string
                    $query_str = "SELECT ";
                    if (in_array("admix", $improvement_status_array)) {
                        $query_str = $query_str . "COUNT(IF(ACD.Subpopulation = 'admix', 1, null)) AS admix, ";
                    }
                    if (in_array("aro", $improvement_status_array)) {
                        $query_str = $query_str . "COUNT(IF(ACD.Subpopulation = 'aro', 1, null)) AS aro, ";
                    }
                    if (in_array("aus", $improvement_status_array)) {
                        $query_str = $query_str . "COUNT(IF(ACD.Subpopulation = 'aus', 1, null)) AS aus, ";
                    }
                    if (in_array("ind1A", $improvement_status_array)) {
                        $query_str = $query_str . "COUNT(IF(ACD.Subpopulation = 'ind1A', 1, null)) AS ind1A, ";
                    }
                    if (in_array("ind1B", $improvement_status_array)) {
                        $query_str = $query_str . "COUNT(IF(ACD.Subpopulation = 'ind1B', 1, null)) AS ind1B, ";
                    }
                    if (in_array("ind2", $improvement_status_array)) {
                        $query_str = $query_str . "COUNT(IF(ACD.Subpopulation = 'ind2', 1, null)) AS ind2, ";
                    }
                    if (in_array("ind3", $improvement_status_array)) {
                        $query_str = $query_str . "COUNT(IF(ACD.Subpopulation = 'ind3', 1, null)) AS ind3, ";
                    }
                    if (in_array("indx", $improvement_status_array)) {
                        $query_str = $query_str . "COUNT(IF(ACD.Subpopulation = 'indx', 1, null)) AS indx, ";
                    }
                    if (in_array("japx", $improvement_status_array)) {
                        $query_str = $query_str . "COUNT(IF(ACD.Subpopulation = 'japx', 1, null)) AS japx, ";
                    }
                    if (in_array("subtrop", $improvement_status_array)) {
                        $query_str = $query_str . "COUNT(IF(ACD.Subpopulation = 'subtrop', 1, null)) AS subtrop, ";
                    }
                    if (in_array("temp", $improvement_status_array)) {
                        $query_str = $query_str . "COUNT(IF(ACD.Subpopulation = 'temp', 1, null)) AS temp, ";
                    }
                    if (in_array("trop", $improvement_status_array)) {
                        $query_str = $query_str . "COUNT(IF(ACD.Subpopulation = 'trop', 1, null)) AS trop, ";
                    }
                    $query_str = $query_str . "COUNT(IF(ACD.Subpopulation IN ('admix', 'aro', 'aus', 'ind1A', 'ind1B', 'ind2', 'ind3', 'indx', 'japx', 'subtrop', 'temp', 'trop'), 1, null)) AS Total, ";
                    $query_str = $query_str . "ACD.Gene, ACD.Chromosome, ACD.Position, ACD.Genotype, ACD.Genotype_Description ";
                    $query_str = $query_str . "FROM ( ";
                    $query_str = $query_str . "    SELECT AM.Subpopulation, GD.Accession, ";
                    $query_str = $query_str . "    GD.Gene, GD.Chromosome, ";
                    $query_str = $query_str . "    GROUP_CONCAT(GD.Position SEPARATOR ' ') AS Position, ";
                    $query_str = $query_str . "    GROUP_CONCAT(GD.Genotype SEPARATOR ' ') AS Genotype, ";
                    $query_str = $query_str . "    GROUP_CONCAT(GD.Genotype_Description SEPARATOR ' ') AS Genotype_Description ";
                    $query_str = $query_str . "    FROM ( ";
                    $query_str = $query_str . "        SELECT G.Chromosome, G.Position, G.Accession, GFF.Gene, G.Genotype, ";
                    $query_str = $query_str . "        CONCAT_WS('|', G.Genotype, G.Functional_Effect, G.Amino_Acid_Change) AS Genotype_Description ";
                    $query_str = $query_str . "        FROM ( ";
                    $query_str = $query_str . "            SELECT Chromosome, Start, End, Name AS Gene ";
                    $query_str = $query_str . "            FROM " . $db . "." . $gff_table . " ";
                    $query_str = $query_str . "            WHERE Name IN ('" . $gene_array[$i] . "') ";
                    $query_str = $query_str . "        ) AS GFF ";
                    $query_str = $query_str . "        INNER JOIN " . $db . ".act_" . $dataset . "_" . $temp_gene_result_arr[0]->Chromosome . " AS G ";
                    $query_str = $query_str . "        ON (G.Chromosome = GFF.Chromosome) AND (G.Position >= GFF.Start) AND (G.Position <= GFF.End) ";
                    $query_str = $query_str . "        ORDER BY G.Position ";
                    $query_str = $query_str . "    ) AS GD ";
                    $query_str = $query_str . "    LEFT JOIN " . $db . "." . $accession_mapping_table . " AS AM ";
                    $query_str = $query_str . "    ON AM.Accession = GD.Accession ";
                    $query_str = $query_str . "    GROUP BY AM.Subpopulation, GD.Accession, GD.Gene, GD.Chromosome ";
                    $query_str = $query_str . ") AS ACD ";
                    $query_str = $query_str . "GROUP BY ACD.Gene, ACD.Chromosome, ACD.Position, ACD.Genotype, ACD.Genotype_Description ";
                    $query_str = $query_str . "ORDER BY ACD.Gene, Total DESC; ";
                }

                $result_arr = DB::connection($db)->select($query_str);

                array_push($gene_result_arr, $temp_gene_result_arr);
                array_push($allele_catalog_result_arr, $result_arr);
            } catch (\Exception $e) {}
        }

        // Package variables that need to go to the view
        $info = [
            'organism' => $organism,
            'dataset' => $dataset,
            'gene_array' => $gene_array,
            'improvement_status_array' => $improvement_status_array,
            'gene_result_arr' => $gene_result_arr,
            'allele_catalog_result_arr' => $allele_catalog_result_arr
        ];

        // Return to view
        return view('system/tools/AlleleCatalogTool2/viewAllByGenes')->with('info', $info);
    }


    public function QueryMetadataByImprovementStatusAndGenotypeCombination(Request $request, $organism) {

        // Database
        $db = "KBC_" . $organism;

        $query_str = "SET SESSION group_concat_max_len = 1000000; ";
        $set_group_concat_max_len_result = DB::connection($db)->select($query_str);

        $organism = $request->Organism;
        $dataset = $request->Dataset;
        $key = $request->Key;
        $gene = $request->Gene;
        $chromosome = $request->Chromosome;
        $position = $request->Position;
        $genotype = $request->Genotype;
        $genotype_description = $request->Genotype_Description;

        // Define key column
        if ($organism == "Zmays") {
            $key_column = "Improvement_Status";
        } elseif ($organism == "Athaliana") {
            $key_column = "Group";
        } elseif ($organism == "Osativa") {
            $key_column = "Subpopulation";
        }

        // Table names and datasets
        if ($organism == "Zmays") {
            $gff_table = "act_Maize_AGPv3_GFF";
            $accession_mapping_table = "act_Maize1210_Accession_Mapping";
        } elseif ($organism == "Athaliana") {
            $gff_table = "act_Arabidopsis_TAIR10_GFF";
            $accession_mapping_table = "act_Arabidopsis1135_Accession_Mapping";
        } elseif ($organism == "Osativa") {
            $gff_table = "act_Rice_Nipponbare_GFF";
            $accession_mapping_table = "act_Rice3000_Accession_Mapping";
        }

        // Generate SQL string
        $query_str = "SELECT Chromosome, Start, End, Name AS Gene ";
        $query_str = $query_str . "FROM " . $db . "." . $gff_table . " ";
        $query_str = $query_str . "WHERE Name IN ('" . $gene . "');";

        $gene_result_arr = DB::connection($db)->select($query_str);

        if ($organism == "Zmays") {
            // Generate SQL string
            $query_str = "SELECT ";
            $query_str = $query_str . "ACD.Kernel_Type, ACD.Improvement_Status, ACD.Country, ACD.State, ";
            $query_str = $query_str . "ACD.Accession, ACD.Panzea_Accession, ";
            $query_str = $query_str . "ACD.Gene, ACD.Chromosome, ACD.Position, ACD.Genotype, ACD.Genotype_Description, ACD.Imputation ";
            $query_str = $query_str . "FROM ( ";
            $query_str = $query_str . "    SELECT AM.Kernel_Type, AM.Improvement_Status, AM.Country, AM.State, ";
            $query_str = $query_str . "    GD.Accession, AM.Panzea_Accession, ";
            $query_str = $query_str . "    GD.Gene, GD.Chromosome, ";
            $query_str = $query_str . "    GROUP_CONCAT(GD.Position SEPARATOR ' ') AS Position, ";
            $query_str = $query_str . "    GROUP_CONCAT(GD.Genotype SEPARATOR ' ') AS Genotype, ";
            $query_str = $query_str . "    GROUP_CONCAT(GD.Genotype_Description SEPARATOR ' ') AS Genotype_Description, ";
            $query_str = $query_str . "    GROUP_CONCAT(GD.Imputation SEPARATOR ' ') AS Imputation ";
            $query_str = $query_str . "    FROM ( ";
            $query_str = $query_str . "        SELECT G.Chromosome, G.Position, G.Accession, GFF.Gene, G.Genotype, ";
            $query_str = $query_str . "        CONCAT_WS('|', G.Genotype, G.Functional_Effect, G.Amino_Acid_Change, G.Imputation) AS Genotype_Description, ";
            $query_str = $query_str . "        G.Imputation ";
            $query_str = $query_str . "        FROM ( ";
            $query_str = $query_str . "            SELECT Chromosome, Start, End, Name AS Gene ";
            $query_str = $query_str . "            FROM " . $db . "." . $gff_table . " ";
            $query_str = $query_str . "            WHERE Name IN ('" . $gene . "') ";
            $query_str = $query_str . "        ) AS GFF ";
            $query_str = $query_str . "        INNER JOIN " . $db . ".act_" . $dataset . "_" . $gene_result_arr[0]->Chromosome . " AS G ";
            $query_str = $query_str . "        ON (G.Chromosome = GFF.Chromosome) AND (G.Position >= GFF.Start) AND (G.Position <= GFF.End) ";
            $query_str = $query_str . "        ORDER BY G.Position ";
            $query_str = $query_str . "    ) AS GD ";
            $query_str = $query_str . "    LEFT JOIN " . $db . "." . $accession_mapping_table . " AS AM ";
            $query_str = $query_str . "    ON AM.Accession = GD.Accession ";
            $query_str = $query_str . "    GROUP BY AM.Kernel_Type, AM.Improvement_Status, AM.Country, AM.State, GD.Accession, AM.Panzea_Accession, GD.Gene, GD.Chromosome ";
            $query_str = $query_str . ") AS ACD ";
            if ($key == "Total") {
                $query_str = $query_str . "WHERE (ACD.Position = '" . $position . "') AND (ACD.Genotype = '" . $genotype . "')";
            } else {
                $query_str = $query_str . "WHERE ";
                $query_str = $query_str . "(ACD." . $key_column . " = '" . $key . "') AND ";
                $query_str = $query_str . "(ACD.Position = '" . $position . "') AND ";
                $query_str = $query_str . "(ACD.Genotype = '" . $genotype . "')";
            }
            $query_str = $query_str . "ORDER BY ACD.Gene; ";

        } elseif ($organism == "Athaliana") {
            // Generate SQL string
            $query_str = "SELECT ";
            $query_str = $query_str . "ACD.Admixture_Group, ACD.Group, ACD.Country, ACD.State, ";
            $query_str = $query_str . "ACD.Accession, ACD.TAIR_Accession, ACD.Name, ";
            $query_str = $query_str . "ACD.Gene, ACD.Chromosome, ACD.Position, ACD.Genotype, ACD.Genotype_Description, ACD.Imputation ";
            $query_str = $query_str . "FROM ( ";
            $query_str = $query_str . "    SELECT AM.Admixture_Group, AM.Group, AM.Country, AM.State, ";
            $query_str = $query_str . "    GD.Accession, AM.TAIR_Accession, AM.Name, ";
            $query_str = $query_str . "    GD.Gene, GD.Chromosome, ";
            $query_str = $query_str . "    GROUP_CONCAT(GD.Position SEPARATOR ' ') AS Position, ";
            $query_str = $query_str . "    GROUP_CONCAT(GD.Genotype SEPARATOR ' ') AS Genotype, ";
            $query_str = $query_str . "    GROUP_CONCAT(GD.Genotype_Description SEPARATOR ' ') AS Genotype_Description, ";
            $query_str = $query_str . "    GROUP_CONCAT(GD.Imputation SEPARATOR ' ') AS Imputation ";
            $query_str = $query_str . "    FROM ( ";
            $query_str = $query_str . "        SELECT G.Chromosome, G.Position, G.Accession, GFF.Gene, G.Genotype, ";
            $query_str = $query_str . "        CONCAT_WS('|', G.Genotype, G.Functional_Effect, G.Amino_Acid_Change, G.Imputation) AS Genotype_Description, ";
            $query_str = $query_str . "        G.Imputation ";
            $query_str = $query_str . "        FROM ( ";
            $query_str = $query_str . "            SELECT Chromosome, Start, End, Name AS Gene ";
            $query_str = $query_str . "            FROM " . $db . "." . $gff_table . " ";
            $query_str = $query_str . "            WHERE Name IN ('" . $gene . "') ";
            $query_str = $query_str . "        ) AS GFF ";
            $query_str = $query_str . "        INNER JOIN " . $db . ".act_" . $dataset . "_" . $gene_result_arr[0]->Chromosome . " AS G ";
            $query_str = $query_str . "        ON (G.Chromosome = GFF.Chromosome) AND (G.Position >= GFF.Start) AND (G.Position <= GFF.End) ";
            $query_str = $query_str . "        ORDER BY G.Position ";
            $query_str = $query_str . "    ) AS GD ";
            $query_str = $query_str . "    LEFT JOIN " . $db . "." . $accession_mapping_table . " AS AM ";
            $query_str = $query_str . "    ON AM.Accession = GD.Accession ";
            $query_str = $query_str . "    GROUP BY AM.Admixture_Group, AM.Group, AM.Country, AM.State, GD.Accession, AM.TAIR_Accession, AM.Name, GD.Gene, GD.Chromosome ";
            $query_str = $query_str . ") AS ACD ";
            if ($key == "Total") {
                $query_str = $query_str . "WHERE (ACD.Position = '" . $position . "') AND (ACD.Genotype = '" . $genotype . "')";
            } else {
                $query_str = $query_str . "WHERE ";
                $query_str = $query_str . "(ACD." . $key_column . " = '" . $key . "') AND ";
                $query_str = $query_str . "(ACD.Position = '" . $position . "') AND ";
                $query_str = $query_str . "(ACD.Genotype = '" . $genotype . "')";
            }
            $query_str = $query_str . "ORDER BY ACD.Gene; ";
        } elseif ($organism == "Osativa") {
            // Generate SQL string
            $query_str = "SELECT ";
            $query_str = $query_str . "ACD.Subpopulation, ACD.Country, ";
            $query_str = $query_str . "ACD.Accession, ";
            $query_str = $query_str . "ACD.Gene, ACD.Chromosome, ACD.Position, ACD.Genotype, ACD.Genotype_Description, ACD.Imputation ";
            $query_str = $query_str . "FROM ( ";
            $query_str = $query_str . "    SELECT AM.Subpopulation, AM.Country, ";
            $query_str = $query_str . "    GD.Accession, ";
            $query_str = $query_str . "    GD.Gene, GD.Chromosome, ";
            $query_str = $query_str . "    GROUP_CONCAT(GD.Position SEPARATOR ' ') AS Position, ";
            $query_str = $query_str . "    GROUP_CONCAT(GD.Genotype SEPARATOR ' ') AS Genotype, ";
            $query_str = $query_str . "    GROUP_CONCAT(GD.Genotype_Description SEPARATOR ' ') AS Genotype_Description, ";
            $query_str = $query_str . "    GROUP_CONCAT(GD.Imputation SEPARATOR ' ') AS Imputation ";
            $query_str = $query_str . "    FROM ( ";
            $query_str = $query_str . "        SELECT G.Chromosome, G.Position, G.Accession, GFF.Gene, G.Genotype, ";
            $query_str = $query_str . "        CONCAT_WS('|', G.Genotype, G.Functional_Effect, G.Amino_Acid_Change, G.Imputation) AS Genotype_Description, ";
            $query_str = $query_str . "        G.Imputation ";
            $query_str = $query_str . "        FROM ( ";
            $query_str = $query_str . "            SELECT Chromosome, Start, End, Name AS Gene ";
            $query_str = $query_str . "            FROM " . $db . "." . $gff_table . " ";
            $query_str = $query_str . "            WHERE Name IN ('" . $gene . "') ";
            $query_str = $query_str . "        ) AS GFF ";
            $query_str = $query_str . "        INNER JOIN " . $db . ".act_" . $dataset . "_" . $gene_result_arr[0]->Chromosome . " AS G ";
            $query_str = $query_str . "        ON (G.Chromosome = GFF.Chromosome) AND (G.Position >= GFF.Start) AND (G.Position <= GFF.End) ";
            $query_str = $query_str . "        ORDER BY G.Position ";
            $query_str = $query_str . "    ) AS GD ";
            $query_str = $query_str . "    LEFT JOIN " . $db . "." . $accession_mapping_table . " AS AM ";
            $query_str = $query_str . "    ON AM.Accession = GD.Accession ";
            $query_str = $query_str . "    GROUP BY AM.Subpopulation, AM.Country, GD.Accession, GD.Gene, GD.Chromosome ";
            $query_str = $query_str . ") AS ACD ";
            if ($key == "Total") {
                $query_str = $query_str . "WHERE (ACD.Position = '" . $position . "') AND (ACD.Genotype = '" . $genotype . "')";
            } else {
                $query_str = $query_str . "WHERE ";
                $query_str = $query_str . "(ACD." . $key_column . " = '" . $key . "') AND ";
                $query_str = $query_str . "(ACD.Position = '" . $position . "') AND ";
                $query_str = $query_str . "(ACD.Genotype = '" . $genotype . "')";
            }
            $query_str = $query_str . "ORDER BY ACD.Gene; ";
        }

        $result_arr = DB::connection($db)->select($query_str);

        return json_encode($result_arr);
    }


    public function QueryAllCountsByGene(Request $request, $organism) {

        // Database
        $db = "KBC_" . $organism;

        $query_str = "SET SESSION group_concat_max_len = 1000000; ";
        $set_group_concat_max_len_result = DB::connection($db)->select($query_str);

        $dataset = $request->Dataset;
        $gene = $request->Gene;
        $improvement_status = $request->Improvement_Status_Array;

        if (is_string($improvement_status)) {
            $improvement_status_array = preg_split("/[;, \n]+/", $improvement_status);
            for ($i = 0; $i < count($improvement_status_array); $i++) {
                $improvement_status_array[$i] = trim($improvement_status_array[$i]);
            }
        } elseif (is_array($improvement_status)) {
            $improvement_status_array = $improvement_status;
            for ($i = 0; $i < count($improvement_status_array); $i++) {
                $improvement_status_array[$i] = trim($improvement_status_array[$i]);
            }
        }

        // Define key column
        if ($organism == "Zmays") {
            $key_column = "Improvement_Status";
        } elseif ($organism == "Athaliana") {
            $key_column = "Group";
        } elseif ($organism == "Osativa") {
            $key_column = "Subpopulation";
        }

        // Table names and datasets
        if ($organism == "Zmays") {
            $gff_table = "act_Maize_AGPv3_GFF";
            $accession_mapping_table = "act_Maize1210_Accession_Mapping";
        } elseif ($organism == "Athaliana") {
            $gff_table = "act_Arabidopsis_TAIR10_GFF";
            $accession_mapping_table = "act_Arabidopsis1135_Accession_Mapping";
        } elseif ($organism == "Osativa") {
            $gff_table = "act_Rice_Nipponbare_GFF";
            $accession_mapping_table = "act_Rice3000_Accession_Mapping";
        }


        // Generate SQL string
        $query_str = "SELECT Chromosome, Start, End, Name AS Gene ";
        $query_str = $query_str . "FROM " . $db . "." . $gff_table . " ";
        $query_str = $query_str . "WHERE Name IN ('" . $gene . "');";

        $gene_result_arr = DB::connection($db)->select($query_str);

        if ($organism == "Zmays") {
            // Generate SQL string
            $query_str = "SELECT ";
            if (in_array("Improved_Cultivar", $improvement_status_array)) {
                $query_str = $query_str . "COUNT(IF(ACD.Improvement_Status = 'Improved_Cultivar', 1, null)) AS Improved_Cultivar, ";
            }
            if (in_array("Landrace", $improvement_status_array)) {
                $query_str = $query_str . "COUNT(IF(ACD.Improvement_Status = 'Landrace', 1, null)) AS Landrace, ";
            }
            if (in_array("Wild_Relative", $improvement_status_array)) {
                $query_str = $query_str . "COUNT(IF(ACD.Improvement_Status = 'Wild_Relative', 1, null)) AS Wild_Relative, ";
            }
            if (in_array("exPVP", $improvement_status_array)) {
                $query_str = $query_str . "COUNT(IF(ACD.Improvement_Status = 'exPVP', 1, null)) AS exPVP, ";
            }
            if (in_array("Other", $improvement_status_array)) {
                $query_str = $query_str . "COUNT(IF(ACD.Improvement_Status = 'Other', 1, null)) AS Other, ";
            }
            $query_str = $query_str . "COUNT(IF(ACD.Improvement_Status IN ('Improved_Cultivar', 'Landrace', 'Wild_Relative', 'exPVP', 'Other'), 1, null)) AS Total, ";
            $query_str = $query_str . "ACD.Gene, ACD.Chromosome, ACD.Position, ACD.Genotype, ACD.Genotype_Description ";
            $query_str = $query_str . "FROM ( ";
            $query_str = $query_str . "    SELECT AM.Improvement_Status, GD.Accession, ";
            $query_str = $query_str . "    GD.Gene, GD.Chromosome, ";
            $query_str = $query_str . "    GROUP_CONCAT(GD.Position SEPARATOR ' ') AS Position, ";
            $query_str = $query_str . "    GROUP_CONCAT(GD.Genotype SEPARATOR ' ') AS Genotype, ";
            $query_str = $query_str . "    GROUP_CONCAT(GD.Genotype_Description SEPARATOR ' ') AS Genotype_Description ";
            $query_str = $query_str . "    FROM ( ";
            $query_str = $query_str . "        SELECT G.Chromosome, G.Position, G.Accession, GFF.Gene, G.Genotype, ";
            $query_str = $query_str . "        CONCAT_WS('|', G.Genotype, G.Functional_Effect, G.Amino_Acid_Change) AS Genotype_Description ";
            $query_str = $query_str . "        FROM ( ";
            $query_str = $query_str . "            SELECT Chromosome, Start, End, Name AS Gene ";
            $query_str = $query_str . "            FROM " . $db . "." . $gff_table . " ";
            $query_str = $query_str . "            WHERE Name IN ('" . $gene . "') ";
            $query_str = $query_str . "        ) AS GFF ";
            $query_str = $query_str . "        INNER JOIN " . $db . ".act_" . $dataset . "_" . $gene_result_arr[0]->Chromosome . " AS G ";
            $query_str = $query_str . "        ON (G.Chromosome = GFF.Chromosome) AND (G.Position >= GFF.Start) AND (G.Position <= GFF.End) ";
            $query_str = $query_str . "        ORDER BY G.Position ";
            $query_str = $query_str . "    ) AS GD ";
            $query_str = $query_str . "    LEFT JOIN " . $db . "." . $accession_mapping_table . " AS AM ";
            $query_str = $query_str . "    ON AM.Accession = GD.Accession ";
            $query_str = $query_str . "    GROUP BY AM.Improvement_Status, GD.Accession, GD.Gene, GD.Chromosome ";
            $query_str = $query_str . ") AS ACD ";
            $query_str = $query_str . "GROUP BY ACD.Gene, ACD.Chromosome, ACD.Position, ACD.Genotype, ACD.Genotype_Description ";
            $query_str = $query_str . "ORDER BY ACD.Gene, Total DESC; ";

        } elseif ($organism == "Athaliana") {
            // Generate SQL string
            $query_str = "SELECT ";
            if (in_array("Central", $improvement_status_array)) {
                $query_str = $query_str . "COUNT(IF(ACD.Group = 'Central', 1, null)) AS Central, ";
            }
            if (in_array("East", $improvement_status_array)) {
                $query_str = $query_str . "COUNT(IF(ACD.Group = 'East', 1, null)) AS East, ";
            }
            if (in_array("North", $improvement_status_array)) {
                $query_str = $query_str . "COUNT(IF(ACD.Group = 'North', 1, null)) AS North, ";
            }
            if (in_array("South", $improvement_status_array)) {
                $query_str = $query_str . "COUNT(IF(ACD.Group = 'South', 1, null)) AS South, ";
            }
            if (in_array("Other", $improvement_status_array)) {
                $query_str = $query_str . "COUNT(IF(ACD.Group = 'Other', 1, null)) AS Other, ";
            }
            $query_str = $query_str . "COUNT(IF(ACD.Group IN ('Central', 'East', 'North', 'South', 'Other'), 1, null)) AS Total, ";
            $query_str = $query_str . "ACD.Gene, ACD.Chromosome, ACD.Position, ACD.Genotype, ACD.Genotype_Description ";
            $query_str = $query_str . "FROM ( ";
            $query_str = $query_str . "    SELECT AM.Group, GD.Accession, ";
            $query_str = $query_str . "    GD.Gene, GD.Chromosome, ";
            $query_str = $query_str . "    GROUP_CONCAT(GD.Position SEPARATOR ' ') AS Position, ";
            $query_str = $query_str . "    GROUP_CONCAT(GD.Genotype SEPARATOR ' ') AS Genotype, ";
            $query_str = $query_str . "    GROUP_CONCAT(GD.Genotype_Description SEPARATOR ' ') AS Genotype_Description ";
            $query_str = $query_str . "    FROM ( ";
            $query_str = $query_str . "        SELECT G.Chromosome, G.Position, G.Accession, GFF.Gene, G.Genotype, ";
            $query_str = $query_str . "        CONCAT_WS('|', G.Genotype, G.Functional_Effect, G.Amino_Acid_Change) AS Genotype_Description ";
            $query_str = $query_str . "        FROM ( ";
            $query_str = $query_str . "            SELECT Chromosome, Start, End, Name AS Gene ";
            $query_str = $query_str . "            FROM " . $db . "." . $gff_table . " ";
            $query_str = $query_str . "            WHERE Name IN ('" . $gene . "') ";
            $query_str = $query_str . "        ) AS GFF ";
            $query_str = $query_str . "        INNER JOIN " . $db . ".act_" . $dataset . "_" . $gene_result_arr[0]->Chromosome . " AS G ";
            $query_str = $query_str . "        ON (G.Chromosome = GFF.Chromosome) AND (G.Position >= GFF.Start) AND (G.Position <= GFF.End) ";
            $query_str = $query_str . "        ORDER BY G.Position ";
            $query_str = $query_str . "    ) AS GD ";
            $query_str = $query_str . "    LEFT JOIN " . $db . "." . $accession_mapping_table . " AS AM ";
            $query_str = $query_str . "    ON AM.Accession = GD.Accession ";
            $query_str = $query_str . "    GROUP BY AM.Group, GD.Accession, GD.Gene, GD.Chromosome ";
            $query_str = $query_str . ") AS ACD ";
            $query_str = $query_str . "GROUP BY ACD.Gene, ACD.Chromosome, ACD.Position, ACD.Genotype, ACD.Genotype_Description ";
            $query_str = $query_str . "ORDER BY ACD.Gene, Total DESC; ";
        } elseif ($organism == "Osativa") {
            // Generate SQL string
            $query_str = "SELECT ";
            if (in_array("admix", $improvement_status_array)) {
                $query_str = $query_str . "COUNT(IF(ACD.Subpopulation = 'admix', 1, null)) AS admix, ";
            }
            if (in_array("aro", $improvement_status_array)) {
                $query_str = $query_str . "COUNT(IF(ACD.Subpopulation = 'aro', 1, null)) AS aro, ";
            }
            if (in_array("aus", $improvement_status_array)) {
                $query_str = $query_str . "COUNT(IF(ACD.Subpopulation = 'aus', 1, null)) AS aus, ";
            }
            if (in_array("ind1A", $improvement_status_array)) {
                $query_str = $query_str . "COUNT(IF(ACD.Subpopulation = 'ind1A', 1, null)) AS ind1A, ";
            }
            if (in_array("ind1B", $improvement_status_array)) {
                $query_str = $query_str . "COUNT(IF(ACD.Subpopulation = 'ind1B', 1, null)) AS ind1B, ";
            }
            if (in_array("ind2", $improvement_status_array)) {
                $query_str = $query_str . "COUNT(IF(ACD.Subpopulation = 'ind2', 1, null)) AS ind2, ";
            }
            if (in_array("ind3", $improvement_status_array)) {
                $query_str = $query_str . "COUNT(IF(ACD.Subpopulation = 'ind3', 1, null)) AS ind3, ";
            }
            if (in_array("indx", $improvement_status_array)) {
                $query_str = $query_str . "COUNT(IF(ACD.Subpopulation = 'indx', 1, null)) AS indx, ";
            }
            if (in_array("japx", $improvement_status_array)) {
                $query_str = $query_str . "COUNT(IF(ACD.Subpopulation = 'japx', 1, null)) AS japx, ";
            }
            if (in_array("subtrop", $improvement_status_array)) {
                $query_str = $query_str . "COUNT(IF(ACD.Subpopulation = 'subtrop', 1, null)) AS subtrop, ";
            }
            if (in_array("temp", $improvement_status_array)) {
                $query_str = $query_str . "COUNT(IF(ACD.Subpopulation = 'temp', 1, null)) AS temp, ";
            }
            if (in_array("trop", $improvement_status_array)) {
                $query_str = $query_str . "COUNT(IF(ACD.Subpopulation = 'trop', 1, null)) AS trop, ";
            }
            $query_str = $query_str . "COUNT(IF(ACD.Subpopulation IN ('admix', 'aro', 'aus', 'ind1A', 'ind1B', 'ind2', 'ind3', 'indx', 'japx', 'subtrop', 'temp', 'trop'), 1, null)) AS Total, ";
            $query_str = $query_str . "ACD.Gene, ACD.Chromosome, ACD.Position, ACD.Genotype, ACD.Genotype_Description ";
            $query_str = $query_str . "FROM ( ";
            $query_str = $query_str . "    SELECT AM.Subpopulation, GD.Accession, ";
            $query_str = $query_str . "    GD.Gene, GD.Chromosome, ";
            $query_str = $query_str . "    GROUP_CONCAT(GD.Position SEPARATOR ' ') AS Position, ";
            $query_str = $query_str . "    GROUP_CONCAT(GD.Genotype SEPARATOR ' ') AS Genotype, ";
            $query_str = $query_str . "    GROUP_CONCAT(GD.Genotype_Description SEPARATOR ' ') AS Genotype_Description ";
            $query_str = $query_str . "    FROM ( ";
            $query_str = $query_str . "        SELECT G.Chromosome, G.Position, G.Accession, GFF.Gene, G.Genotype, ";
            $query_str = $query_str . "        CONCAT_WS('|', G.Genotype, G.Functional_Effect, G.Amino_Acid_Change) AS Genotype_Description ";
            $query_str = $query_str . "        FROM ( ";
            $query_str = $query_str . "            SELECT Chromosome, Start, End, Name AS Gene ";
            $query_str = $query_str . "            FROM " . $db . "." . $gff_table . " ";
            $query_str = $query_str . "            WHERE Name IN ('" . $gene . "') ";
            $query_str = $query_str . "        ) AS GFF ";
            $query_str = $query_str . "        INNER JOIN " . $db . ".act_" . $dataset . "_" . $gene_result_arr[0]->Chromosome . " AS G ";
            $query_str = $query_str . "        ON (G.Chromosome = GFF.Chromosome) AND (G.Position >= GFF.Start) AND (G.Position <= GFF.End) ";
            $query_str = $query_str . "        ORDER BY G.Position ";
            $query_str = $query_str . "    ) AS GD ";
            $query_str = $query_str . "    LEFT JOIN " . $db . "." . $accession_mapping_table . " AS AM ";
            $query_str = $query_str . "    ON AM.Accession = GD.Accession ";
            $query_str = $query_str . "    GROUP BY AM.Subpopulation, GD.Accession, GD.Gene, GD.Chromosome ";
            $query_str = $query_str . ") AS ACD ";
            $query_str = $query_str . "GROUP BY ACD.Gene, ACD.Chromosome, ACD.Position, ACD.Genotype, ACD.Genotype_Description ";
            $query_str = $query_str . "ORDER BY ACD.Gene, Total DESC; ";
        }

        $result_arr = DB::connection($db)->select($query_str);

        return json_encode($result_arr);
    }


    public function QueryAllByGene(Request $request, $organism) {

        // Database
        $db = "KBC_" . $organism;

        $dataset = $request->Dataset;
        $gene = $request->Gene;
        $improvement_status = $request->Improvement_Status_Array;

        if (is_string($improvement_status)) {
            $improvement_status_array = preg_split("/[;, \n]+/", $improvement_status);
            for ($i = 0; $i < count($improvement_status_array); $i++) {
                $improvement_status_array[$i] = trim($improvement_status_array[$i]);
            }
        } elseif (is_array($improvement_status)) {
            $improvement_status_array = $improvement_status;
            for ($i = 0; $i < count($improvement_status_array); $i++) {
                $improvement_status_array[$i] = trim($improvement_status_array[$i]);
            }
        }

        // Define key column
        if ($organism == "Zmays") {
            $key_column = "Improvement_Status";
        } elseif ($organism == "Athaliana") {
            $key_column = "Group";
        } elseif ($organism == "Osativa") {
            $key_column = "Subpopulation";
        }

        // Table names and datasets
        if ($organism == "Zmays") {
            $gff_table = "act_Maize_AGPv3_GFF";
            $accession_mapping_table = "act_Maize1210_Accession_Mapping";
        } elseif ($organism == "Athaliana") {
            $gff_table = "act_Arabidopsis_TAIR10_GFF";
            $accession_mapping_table = "act_Arabidopsis1135_Accession_Mapping";
        } elseif ($organism == "Osativa") {
            $gff_table = "act_Rice_Nipponbare_GFF";
            $accession_mapping_table = "act_Rice3000_Accession_Mapping";
        }

        // Generate SQL string
        $query_str = "SELECT Chromosome, Start, End, Name AS Gene ";
        $query_str = $query_str . "FROM " . $db . "." . $gff_table . " ";
        $query_str = $query_str . "WHERE Name IN ('" . $gene . "');";

        $gene_result_arr = DB::connection($db)->select($query_str);


        if ($organism == "Zmays") {
            // Generate SQL string
            $query_str = "SELECT ";
            $query_str = $query_str . "ACD.Kernel_Type, ACD.Improvement_Status, ACD.Country, ACD.State, ";
            $query_str = $query_str . "ACD.Accession, ACD.Panzea_Accession, ";
            $query_str = $query_str . "ACD.Gene, ACD.Chromosome, ACD.Position, ACD.Genotype, ACD.Genotype_Description, ACD.Imputation ";
            $query_str = $query_str . "FROM ( ";
            $query_str = $query_str . "    SELECT AM.Kernel_Type, AM.Improvement_Status, AM.Country, AM.State, ";
            $query_str = $query_str . "    GD.Accession, AM.Panzea_Accession, ";
            $query_str = $query_str . "    GD.Gene, GD.Chromosome, ";
            $query_str = $query_str . "    GROUP_CONCAT(GD.Position SEPARATOR ' ') AS Position, ";
            $query_str = $query_str . "    GROUP_CONCAT(GD.Genotype SEPARATOR ' ') AS Genotype, ";
            $query_str = $query_str . "    GROUP_CONCAT(GD.Genotype_Description SEPARATOR ' ') AS Genotype_Description, ";
            $query_str = $query_str . "    GROUP_CONCAT(GD.Imputation SEPARATOR ' ') AS Imputation ";
            $query_str = $query_str . "    FROM ( ";
            $query_str = $query_str . "        SELECT G.Chromosome, G.Position, G.Accession, GFF.Gene, G.Genotype, ";
            $query_str = $query_str . "        CONCAT_WS('|', G.Genotype, G.Functional_Effect, G.Amino_Acid_Change, G.Imputation) AS Genotype_Description, ";
            $query_str = $query_str . "        G.Imputation ";
            $query_str = $query_str . "        FROM ( ";
            $query_str = $query_str . "            SELECT Chromosome, Start, End, Name AS Gene ";
            $query_str = $query_str . "            FROM " . $db . "." . $gff_table . " ";
            $query_str = $query_str . "            WHERE Name IN ('" . $gene . "') ";
            $query_str = $query_str . "        ) AS GFF ";
            $query_str = $query_str . "        INNER JOIN " . $db . ".act_" . $dataset . "_" . $gene_result_arr[0]->Chromosome . " AS G ";
            $query_str = $query_str . "        ON (G.Chromosome = GFF.Chromosome) AND (G.Position >= GFF.Start) AND (G.Position <= GFF.End) ";
            $query_str = $query_str . "        ORDER BY G.Position ";
            $query_str = $query_str . "    ) AS GD ";
            $query_str = $query_str . "    LEFT JOIN " . $db . "." . $accession_mapping_table . " AS AM ";
            $query_str = $query_str . "    ON AM.Accession = GD.Accession ";
            $query_str = $query_str . "    GROUP BY AM.Kernel_Type, AM.Improvement_Status, AM.Country, AM.State, GD.Accession, AM.Panzea_Accession, GD.Gene, GD.Chromosome ";
            $query_str = $query_str . ") AS ACD ";
            $query_str = $query_str . "ORDER BY ACD.Gene; ";

        } elseif ($organism == "Athaliana") {
            // Generate SQL string
            $query_str = "SELECT ";
            $query_str = $query_str . "ACD.Admixture_Group, ACD.Group, ACD.Country, ACD.State, ";
            $query_str = $query_str . "ACD.Accession, ACD.TAIR_Accession, ACD.Name, ";
            $query_str = $query_str . "ACD.Gene, ACD.Chromosome, ACD.Position, ACD.Genotype, ACD.Genotype_Description, ACD.Imputation ";
            $query_str = $query_str . "FROM ( ";
            $query_str = $query_str . "    SELECT AM.Admixture_Group, AM.Group, AM.Country, AM.State, ";
            $query_str = $query_str . "    GD.Accession, AM.TAIR_Accession, AM.Name, ";
            $query_str = $query_str . "    GD.Gene, GD.Chromosome, ";
            $query_str = $query_str . "    GROUP_CONCAT(GD.Position SEPARATOR ' ') AS Position, ";
            $query_str = $query_str . "    GROUP_CONCAT(GD.Genotype SEPARATOR ' ') AS Genotype, ";
            $query_str = $query_str . "    GROUP_CONCAT(GD.Genotype_Description SEPARATOR ' ') AS Genotype_Description, ";
            $query_str = $query_str . "    GROUP_CONCAT(GD.Imputation SEPARATOR ' ') AS Imputation ";
            $query_str = $query_str . "    FROM ( ";
            $query_str = $query_str . "        SELECT G.Chromosome, G.Position, G.Accession, GFF.Gene, G.Genotype, ";
            $query_str = $query_str . "        CONCAT_WS('|', G.Genotype, G.Functional_Effect, G.Amino_Acid_Change, G.Imputation) AS Genotype_Description, ";
            $query_str = $query_str . "        G.Imputation ";
            $query_str = $query_str . "        FROM ( ";
            $query_str = $query_str . "            SELECT Chromosome, Start, End, Name AS Gene ";
            $query_str = $query_str . "            FROM " . $db . "." . $gff_table . " ";
            $query_str = $query_str . "            WHERE Name IN ('" . $gene . "') ";
            $query_str = $query_str . "        ) AS GFF ";
            $query_str = $query_str . "        INNER JOIN " . $db . ".act_" . $dataset . "_" . $gene_result_arr[0]->Chromosome . " AS G ";
            $query_str = $query_str . "        ON (G.Chromosome = GFF.Chromosome) AND (G.Position >= GFF.Start) AND (G.Position <= GFF.End) ";
            $query_str = $query_str . "        ORDER BY G.Position ";
            $query_str = $query_str . "    ) AS GD ";
            $query_str = $query_str . "    LEFT JOIN " . $db . "." . $accession_mapping_table . " AS AM ";
            $query_str = $query_str . "    ON AM.Accession = GD.Accession ";
            $query_str = $query_str . "    GROUP BY AM.Admixture_Group, AM.Group, AM.Country, AM.State, GD.Accession, AM.TAIR_Accession, AM.Name, GD.Gene, GD.Chromosome ";
            $query_str = $query_str . ") AS ACD ";
            $query_str = $query_str . "ORDER BY ACD.Gene; ";
        } elseif ($organism == "Osativa") {
            // Generate SQL string
            $query_str = "SELECT ";
            $query_str = $query_str . "ACD.Subpopulation, ACD.Country, ";
            $query_str = $query_str . "ACD.Accession, ";
            $query_str = $query_str . "ACD.Gene, ACD.Chromosome, ACD.Position, ACD.Genotype, ACD.Genotype_Description, ACD.Imputation ";
            $query_str = $query_str . "FROM ( ";
            $query_str = $query_str . "    SELECT AM.Subpopulation, AM.Country, ";
            $query_str = $query_str . "    GD.Accession, ";
            $query_str = $query_str . "    GD.Gene, GD.Chromosome, ";
            $query_str = $query_str . "    GROUP_CONCAT(GD.Position SEPARATOR ' ') AS Position, ";
            $query_str = $query_str . "    GROUP_CONCAT(GD.Genotype SEPARATOR ' ') AS Genotype, ";
            $query_str = $query_str . "    GROUP_CONCAT(GD.Genotype_Description SEPARATOR ' ') AS Genotype_Description, ";
            $query_str = $query_str . "    GROUP_CONCAT(GD.Imputation SEPARATOR ' ') AS Imputation ";
            $query_str = $query_str . "    FROM ( ";
            $query_str = $query_str . "        SELECT G.Chromosome, G.Position, G.Accession, GFF.Gene, G.Genotype, ";
            $query_str = $query_str . "        CONCAT_WS('|', G.Genotype, G.Functional_Effect, G.Amino_Acid_Change, G.Imputation) AS Genotype_Description, ";
            $query_str = $query_str . "        G.Imputation ";
            $query_str = $query_str . "        FROM ( ";
            $query_str = $query_str . "            SELECT Chromosome, Start, End, Name AS Gene ";
            $query_str = $query_str . "            FROM " . $db . "." . $gff_table . " ";
            $query_str = $query_str . "            WHERE Name IN ('" . $gene . "') ";
            $query_str = $query_str . "        ) AS GFF ";
            $query_str = $query_str . "        INNER JOIN " . $db . ".act_" . $dataset . "_" . $gene_result_arr[0]->Chromosome . " AS G ";
            $query_str = $query_str . "        ON (G.Chromosome = GFF.Chromosome) AND (G.Position >= GFF.Start) AND (G.Position <= GFF.End) ";
            $query_str = $query_str . "        ORDER BY G.Position ";
            $query_str = $query_str . "    ) AS GD ";
            $query_str = $query_str . "    LEFT JOIN " . $db . "." . $accession_mapping_table . " AS AM ";
            $query_str = $query_str . "    ON AM.Accession = GD.Accession ";
            $query_str = $query_str . "    GROUP BY AM.Subpopulation, AM.Country, GD.Accession, GD.Gene, GD.Chromosome ";
            $query_str = $query_str . ") AS ACD ";
            $query_str = $query_str . "ORDER BY ACD.Gene; ";
        }

        $result_arr = DB::connection($db)->select($query_str);

        for ($i = 0; $i < count($result_arr); $i++) {
            if (preg_match("/\+/i", $result_arr[$i]->Imputation)) {
                $result_arr[$i]->Imputation = "+";
            } else{
                $result_arr[$i]->Imputation = "";
            }
        }

        return json_encode($result_arr);
    }


    public function QueryAllCountsByMultipleGenes(Request $request, $organism) {

        // Database
        $db = "KBC_" . $organism;

        $query_str = "SET SESSION group_concat_max_len = 1000000; ";
        $set_group_concat_max_len_result = DB::connection($db)->select($query_str);

        $dataset = $request->Dataset;
        $gene = $request->Gene_Array;
        $improvement_status = $request->Improvement_Status_Array;

        if (is_string($gene)) {
            $gene_array = preg_split("/[;, \n]+/", $gene);
            for ($i = 0; $i < count($gene_array); $i++) {
                $gene_array[$i] = trim($gene_array[$i]);
            }
        } elseif (is_array($gene)) {
            $gene_array = $gene;
            for ($i = 0; $i < count($gene_array); $i++) {
                $gene_array[$i] = trim($gene_array[$i]);
            }
        }

        if (is_string($improvement_status)) {
            $improvement_status_array = preg_split("/[;, \n]+/", $improvement_status);
            for ($i = 0; $i < count($improvement_status_array); $i++) {
                $improvement_status_array[$i] = trim($improvement_status_array[$i]);
            }
        } elseif (is_array($improvement_status)) {
            $improvement_status_array = $improvement_status;
            for ($i = 0; $i < count($improvement_status_array); $i++) {
                $improvement_status_array[$i] = trim($improvement_status_array[$i]);
            }
        }

        // Define key column
        if ($organism == "Zmays") {
            $key_column = "Improvement_Status";
        } elseif ($organism == "Athaliana") {
            $key_column = "Group";
        } elseif ($organism == "Osativa") {
            $key_column = "Subpopulation";
        }

        // Table names and datasets
        if ($organism == "Zmays") {
            $gff_table = "act_Maize_AGPv3_GFF";
            $accession_mapping_table = "act_Maize1210_Accession_Mapping";
        } elseif ($organism == "Athaliana") {
            $gff_table = "act_Arabidopsis_TAIR10_GFF";
            $accession_mapping_table = "act_Arabidopsis1135_Accession_Mapping";
        } elseif ($organism == "Osativa") {
            $gff_table = "act_Rice_Nipponbare_GFF";
            $accession_mapping_table = "act_Rice3000_Accession_Mapping";
        }

        for ($i = 0; $i < count($gene_array); $i++) {

            // Generate SQL string
            $query_str = "SELECT Chromosome, Start, End, Name AS Gene ";
            $query_str = $query_str . "FROM " . $db . "." . $gff_table . " ";
            $query_str = $query_str . "WHERE Name IN ('" . $gene_array[$i] . "');";

            $temp_gene_result_arr = DB::connection($db)->select($query_str);

            if ($organism == "Zmays") {
                // Generate SQL string
                $query_str = "SELECT ";
                if (in_array("Improved_Cultivar", $improvement_status_array)) {
                    $query_str = $query_str . "COUNT(IF(ACD.Improvement_Status = 'Improved_Cultivar', 1, null)) AS Improved_Cultivar, ";
                }
                if (in_array("Landrace", $improvement_status_array)) {
                    $query_str = $query_str . "COUNT(IF(ACD.Improvement_Status = 'Landrace', 1, null)) AS Landrace, ";
                }
                if (in_array("Wild_Relative", $improvement_status_array)) {
                    $query_str = $query_str . "COUNT(IF(ACD.Improvement_Status = 'Wild_Relative', 1, null)) AS Wild_Relative, ";
                }
                if (in_array("exPVP", $improvement_status_array)) {
                    $query_str = $query_str . "COUNT(IF(ACD.Improvement_Status = 'exPVP', 1, null)) AS exPVP, ";
                }
                if (in_array("Other", $improvement_status_array)) {
                    $query_str = $query_str . "COUNT(IF(ACD.Improvement_Status = 'Other', 1, null)) AS Other, ";
                }
                $query_str = $query_str . "COUNT(IF(ACD.Improvement_Status IN ('Improved_Cultivar', 'Landrace', 'Wild_Relative', 'exPVP', 'Other'), 1, null)) AS Total, ";
                $query_str = $query_str . "ACD.Gene, ACD.Chromosome, ACD.Position, ACD.Genotype, ACD.Genotype_Description ";
                $query_str = $query_str . "FROM ( ";
                $query_str = $query_str . "    SELECT AM.Improvement_Status, GD.Accession, ";
                $query_str = $query_str . "    GD.Gene, GD.Chromosome, ";
                $query_str = $query_str . "    GROUP_CONCAT(GD.Position SEPARATOR ' ') AS Position, ";
                $query_str = $query_str . "    GROUP_CONCAT(GD.Genotype SEPARATOR ' ') AS Genotype, ";
                $query_str = $query_str . "    GROUP_CONCAT(GD.Genotype_Description SEPARATOR ' ') AS Genotype_Description ";
                $query_str = $query_str . "    FROM ( ";
                $query_str = $query_str . "        SELECT G.Chromosome, G.Position, G.Accession, GFF.Gene, G.Genotype, ";
                $query_str = $query_str . "        CONCAT_WS('|', G.Genotype, G.Functional_Effect, G.Amino_Acid_Change) AS Genotype_Description ";
                $query_str = $query_str . "        FROM ( ";
                $query_str = $query_str . "            SELECT Chromosome, Start, End, Name AS Gene ";
                $query_str = $query_str . "            FROM " . $db . "." . $gff_table . " ";
                $query_str = $query_str . "            WHERE Name IN ('" . $gene_array[$i] . "') ";
                $query_str = $query_str . "        ) AS GFF ";
                $query_str = $query_str . "        INNER JOIN " . $db . ".act_" . $dataset . "_" . $temp_gene_result_arr[0]->Chromosome . " AS G ";
                $query_str = $query_str . "        ON (G.Chromosome = GFF.Chromosome) AND (G.Position >= GFF.Start) AND (G.Position <= GFF.End) ";
                $query_str = $query_str . "        ORDER BY G.Position ";
                $query_str = $query_str . "    ) AS GD ";
                $query_str = $query_str . "    LEFT JOIN " . $db . "." . $accession_mapping_table . " AS AM ";
                $query_str = $query_str . "    ON AM.Accession = GD.Accession ";
                $query_str = $query_str . "    GROUP BY AM.Improvement_Status, GD.Accession, GD.Gene, GD.Chromosome ";
                $query_str = $query_str . ") AS ACD ";
                $query_str = $query_str . "GROUP BY ACD.Gene, ACD.Chromosome, ACD.Position, ACD.Genotype, ACD.Genotype_Description ";
                $query_str = $query_str . "ORDER BY ACD.Gene, Total DESC; ";

            } elseif ($organism == "Athaliana") {
                // Generate SQL string
                $query_str = "SELECT ";
                if (in_array("Central", $improvement_status_array)) {
                    $query_str = $query_str . "COUNT(IF(ACD.Group = 'Central', 1, null)) AS Central, ";
                }
                if (in_array("East", $improvement_status_array)) {
                    $query_str = $query_str . "COUNT(IF(ACD.Group = 'East', 1, null)) AS East, ";
                }
                if (in_array("North", $improvement_status_array)) {
                    $query_str = $query_str . "COUNT(IF(ACD.Group = 'North', 1, null)) AS North, ";
                }
                if (in_array("South", $improvement_status_array)) {
                    $query_str = $query_str . "COUNT(IF(ACD.Group = 'South', 1, null)) AS South, ";
                }
                if (in_array("Other", $improvement_status_array)) {
                    $query_str = $query_str . "COUNT(IF(ACD.Group = 'Other', 1, null)) AS Other, ";
                }
                $query_str = $query_str . "COUNT(IF(ACD.Group IN ('Central', 'East', 'North', 'South', 'Other'), 1, null)) AS Total, ";
                $query_str = $query_str . "ACD.Gene, ACD.Chromosome, ACD.Position, ACD.Genotype, ACD.Genotype_Description ";
                $query_str = $query_str . "FROM ( ";
                $query_str = $query_str . "    SELECT AM.Group, GD.Accession, ";
                $query_str = $query_str . "    GD.Gene, GD.Chromosome, ";
                $query_str = $query_str . "    GROUP_CONCAT(GD.Position SEPARATOR ' ') AS Position, ";
                $query_str = $query_str . "    GROUP_CONCAT(GD.Genotype SEPARATOR ' ') AS Genotype, ";
                $query_str = $query_str . "    GROUP_CONCAT(GD.Genotype_Description SEPARATOR ' ') AS Genotype_Description ";
                $query_str = $query_str . "    FROM ( ";
                $query_str = $query_str . "        SELECT G.Chromosome, G.Position, G.Accession, GFF.Gene, G.Genotype, ";
                $query_str = $query_str . "        CONCAT_WS('|', G.Genotype, G.Functional_Effect, G.Amino_Acid_Change) AS Genotype_Description ";
                $query_str = $query_str . "        FROM ( ";
                $query_str = $query_str . "            SELECT Chromosome, Start, End, Name AS Gene ";
                $query_str = $query_str . "            FROM " . $db . "." . $gff_table . " ";
                $query_str = $query_str . "            WHERE Name IN ('" . $gene_array[$i] . "') ";
                $query_str = $query_str . "        ) AS GFF ";
                $query_str = $query_str . "        INNER JOIN " . $db . ".act_" . $dataset . "_" . $temp_gene_result_arr[0]->Chromosome . " AS G ";
                $query_str = $query_str . "        ON (G.Chromosome = GFF.Chromosome) AND (G.Position >= GFF.Start) AND (G.Position <= GFF.End) ";
                $query_str = $query_str . "        ORDER BY G.Position ";
                $query_str = $query_str . "    ) AS GD ";
                $query_str = $query_str . "    LEFT JOIN " . $db . "." . $accession_mapping_table . " AS AM ";
                $query_str = $query_str . "    ON AM.Accession = GD.Accession ";
                $query_str = $query_str . "    GROUP BY AM.Group, GD.Accession, GD.Gene, GD.Chromosome ";
                $query_str = $query_str . ") AS ACD ";
                $query_str = $query_str . "GROUP BY ACD.Gene, ACD.Chromosome, ACD.Position, ACD.Genotype, ACD.Genotype_Description ";
                $query_str = $query_str . "ORDER BY ACD.Gene, Total DESC; ";
            } elseif ($organism == "Osativa") {
                $query_str = "SELECT ";
                if (in_array("admix", $improvement_status_array)) {
                    $query_str = $query_str . "COUNT(IF(ACD.Subpopulation = 'admix', 1, null)) AS admix, ";
                }
                if (in_array("aro", $improvement_status_array)) {
                    $query_str = $query_str . "COUNT(IF(ACD.Subpopulation = 'aro', 1, null)) AS aro, ";
                }
                if (in_array("aus", $improvement_status_array)) {
                    $query_str = $query_str . "COUNT(IF(ACD.Subpopulation = 'aus', 1, null)) AS aus, ";
                }
                if (in_array("ind1A", $improvement_status_array)) {
                    $query_str = $query_str . "COUNT(IF(ACD.Subpopulation = 'ind1A', 1, null)) AS ind1A, ";
                }
                if (in_array("ind1B", $improvement_status_array)) {
                    $query_str = $query_str . "COUNT(IF(ACD.Subpopulation = 'ind1B', 1, null)) AS ind1B, ";
                }
                if (in_array("ind2", $improvement_status_array)) {
                    $query_str = $query_str . "COUNT(IF(ACD.Subpopulation = 'ind2', 1, null)) AS ind2, ";
                }
                if (in_array("ind3", $improvement_status_array)) {
                    $query_str = $query_str . "COUNT(IF(ACD.Subpopulation = 'ind3', 1, null)) AS ind3, ";
                }
                if (in_array("indx", $improvement_status_array)) {
                    $query_str = $query_str . "COUNT(IF(ACD.Subpopulation = 'indx', 1, null)) AS indx, ";
                }
                if (in_array("japx", $improvement_status_array)) {
                    $query_str = $query_str . "COUNT(IF(ACD.Subpopulation = 'japx', 1, null)) AS japx, ";
                }
                if (in_array("subtrop", $improvement_status_array)) {
                    $query_str = $query_str . "COUNT(IF(ACD.Subpopulation = 'subtrop', 1, null)) AS subtrop, ";
                }
                if (in_array("temp", $improvement_status_array)) {
                    $query_str = $query_str . "COUNT(IF(ACD.Subpopulation = 'temp', 1, null)) AS temp, ";
                }
                if (in_array("trop", $improvement_status_array)) {
                    $query_str = $query_str . "COUNT(IF(ACD.Subpopulation = 'trop', 1, null)) AS trop, ";
                }
                $query_str = $query_str . "COUNT(IF(ACD.Subpopulation IN ('admix', 'aro', 'aus', 'ind1A', 'ind1B', 'ind2', 'ind3', 'indx', 'japx', 'subtrop', 'temp', 'trop'), 1, null)) AS Total, ";
                $query_str = $query_str . "ACD.Gene, ACD.Chromosome, ACD.Position, ACD.Genotype, ACD.Genotype_Description ";
                $query_str = $query_str . "FROM ( ";
                $query_str = $query_str . "    SELECT AM.Subpopulation, GD.Accession, ";
                $query_str = $query_str . "    GD.Gene, GD.Chromosome, ";
                $query_str = $query_str . "    GROUP_CONCAT(GD.Position SEPARATOR ' ') AS Position, ";
                $query_str = $query_str . "    GROUP_CONCAT(GD.Genotype SEPARATOR ' ') AS Genotype, ";
                $query_str = $query_str . "    GROUP_CONCAT(GD.Genotype_Description SEPARATOR ' ') AS Genotype_Description ";
                $query_str = $query_str . "    FROM ( ";
                $query_str = $query_str . "        SELECT G.Chromosome, G.Position, G.Accession, GFF.Gene, G.Genotype, ";
                $query_str = $query_str . "        CONCAT_WS('|', G.Genotype, G.Functional_Effect, G.Amino_Acid_Change) AS Genotype_Description ";
                $query_str = $query_str . "        FROM ( ";
                $query_str = $query_str . "            SELECT Chromosome, Start, End, Name AS Gene ";
                $query_str = $query_str . "            FROM " . $db . "." . $gff_table . " ";
                $query_str = $query_str . "            WHERE Name IN ('" . $gene_array[$i] . "') ";
                $query_str = $query_str . "        ) AS GFF ";
                $query_str = $query_str . "        INNER JOIN " . $db . ".act_" . $dataset . "_" . $temp_gene_result_arr[0]->Chromosome . " AS G ";
                $query_str = $query_str . "        ON (G.Chromosome = GFF.Chromosome) AND (G.Position >= GFF.Start) AND (G.Position <= GFF.End) ";
                $query_str = $query_str . "        ORDER BY G.Position ";
                $query_str = $query_str . "    ) AS GD ";
                $query_str = $query_str . "    LEFT JOIN " . $db . "." . $accession_mapping_table . " AS AM ";
                $query_str = $query_str . "    ON AM.Accession = GD.Accession ";
                $query_str = $query_str . "    GROUP BY AM.Subpopulation, GD.Accession, GD.Gene, GD.Chromosome ";
                $query_str = $query_str . ") AS ACD ";
                $query_str = $query_str . "GROUP BY ACD.Gene, ACD.Chromosome, ACD.Position, ACD.Genotype, ACD.Genotype_Description ";
                $query_str = $query_str . "ORDER BY ACD.Gene, Total DESC; ";
            }

            $result_arr = DB::connection($db)->select($query_str);

            if (!isset($allele_catalog_result_arr)){
                $allele_catalog_result_arr = (array) $result_arr;
            } else {
                $allele_catalog_result_arr = array_merge($allele_catalog_result_arr, (array) $result_arr);
            }
        }

        return json_encode($allele_catalog_result_arr);
    }


    public function QueryAllByMultipleGenes(Request $request, $organism) {

        // Database
        $db = "KBC_" . $organism;

        $dataset = $request->Dataset;
        $gene = $request->Gene_Array;
        $improvement_status = $request->Improvement_Status_Array;

        if (is_string($gene)) {
            $gene_array = preg_split("/[;, \n]+/", $gene);
            for ($i = 0; $i < count($gene_array); $i++) {
                $gene_array[$i] = trim($gene_array[$i]);
            }
        } elseif (is_array($gene)) {
            $gene_array = $gene;
            for ($i = 0; $i < count($gene_array); $i++) {
                $gene_array[$i] = trim($gene_array[$i]);
            }
        }

        if (is_string($improvement_status)) {
            $improvement_status_array = preg_split("/[;, \n]+/", $improvement_status);
            for ($i = 0; $i < count($improvement_status_array); $i++) {
                $improvement_status_array[$i] = trim($improvement_status_array[$i]);
            }
        } elseif (is_array($improvement_status)) {
            $improvement_status_array = $improvement_status;
            for ($i = 0; $i < count($improvement_status_array); $i++) {
                $improvement_status_array[$i] = trim($improvement_status_array[$i]);
            }
        }

        // Define key column
        if ($organism == "Zmays") {
            $key_column = "Improvement_Status";
        } elseif ($organism == "Athaliana") {
            $key_column = "Group";
        } elseif ($organism == "Osativa") {
            $key_column = "Subpopulation";
        }

        // Table names and datasets
        if ($organism == "Zmays") {
            $gff_table = "act_Maize_AGPv3_GFF";
            $accession_mapping_table = "act_Maize1210_Accession_Mapping";
        } elseif ($organism == "Athaliana") {
            $gff_table = "act_Arabidopsis_TAIR10_GFF";
            $accession_mapping_table = "act_Arabidopsis1135_Accession_Mapping";
        } elseif ($organism == "Osativa") {
            $gff_table = "act_Rice_Nipponbare_GFF";
            $accession_mapping_table = "act_Rice3000_Accession_Mapping";
        }

        for ($i = 0; $i < count($gene_array); $i++) {

            // Generate SQL string
            $query_str = "SELECT Chromosome, Start, End, Name AS Gene ";
            $query_str = $query_str . "FROM " . $db . "." . $gff_table . " ";
            $query_str = $query_str . "WHERE Name IN ('" . $gene_array[$i] . "');";

            $temp_gene_result_arr = DB::connection($db)->select($query_str);

            if ($organism == "Zmays") {
                // Generate SQL string
                $query_str = "SELECT ";
                $query_str = $query_str . "ACD.Kernel_Type, ACD.Improvement_Status, ACD.Country, ACD.State, ";
                $query_str = $query_str . "ACD.Accession, ACD.Panzea_Accession, ";
                $query_str = $query_str . "ACD.Gene, ACD.Chromosome, ACD.Position, ACD.Genotype, ACD.Genotype_Description, ACD.Imputation ";
                $query_str = $query_str . "FROM ( ";
                $query_str = $query_str . "    SELECT AM.Kernel_Type, AM.Improvement_Status, AM.Country, AM.State, ";
                $query_str = $query_str . "    GD.Accession, AM.Panzea_Accession, ";
                $query_str = $query_str . "    GD.Gene, GD.Chromosome, ";
                $query_str = $query_str . "    GROUP_CONCAT(GD.Position SEPARATOR ' ') AS Position, ";
                $query_str = $query_str . "    GROUP_CONCAT(GD.Genotype SEPARATOR ' ') AS Genotype, ";
                $query_str = $query_str . "    GROUP_CONCAT(GD.Genotype_Description SEPARATOR ' ') AS Genotype_Description, ";
                $query_str = $query_str . "    GROUP_CONCAT(GD.Imputation SEPARATOR ' ') AS Imputation ";
                $query_str = $query_str . "    FROM ( ";
                $query_str = $query_str . "        SELECT G.Chromosome, G.Position, G.Accession, GFF.Gene, G.Genotype, ";
                $query_str = $query_str . "        CONCAT_WS('|', G.Genotype, G.Functional_Effect, G.Amino_Acid_Change, G.Imputation) AS Genotype_Description, ";
                $query_str = $query_str . "        G.Imputation ";
                $query_str = $query_str . "        FROM ( ";
                $query_str = $query_str . "            SELECT Chromosome, Start, End, Name AS Gene ";
                $query_str = $query_str . "            FROM " . $db . "." . $gff_table . " ";
                $query_str = $query_str . "            WHERE Name IN ('" . $gene_array[$i] . "') ";
                $query_str = $query_str . "        ) AS GFF ";
                $query_str = $query_str . "        INNER JOIN " . $db . ".act_" . $dataset . "_" . $temp_gene_result_arr[0]->Chromosome . " AS G ";
                $query_str = $query_str . "        ON (G.Chromosome = GFF.Chromosome) AND (G.Position >= GFF.Start) AND (G.Position <= GFF.End) ";
                $query_str = $query_str . "        ORDER BY G.Position ";
                $query_str = $query_str . "    ) AS GD ";
                $query_str = $query_str . "    LEFT JOIN " . $db . "." . $accession_mapping_table . " AS AM ";
                $query_str = $query_str . "    ON AM.Accession = GD.Accession ";
                $query_str = $query_str . "    GROUP BY AM.Kernel_Type, AM.Improvement_Status, AM.Country, AM.State, GD.Accession, AM.Panzea_Accession, GD.Gene, GD.Chromosome ";
                $query_str = $query_str . ") AS ACD ";
                $query_str = $query_str . "ORDER BY ACD.Gene; ";

            } elseif ($organism == "Athaliana") {
                // Generate SQL string
                $query_str = "SELECT ";
                $query_str = $query_str . "ACD.Admixture_Group, ACD.Group, ACD.Country, ACD.State, ";
                $query_str = $query_str . "ACD.Accession, ACD.TAIR_Accession, ACD.Name, ";
                $query_str = $query_str . "ACD.Gene, ACD.Chromosome, ACD.Position, ACD.Genotype, ACD.Genotype_Description, ACD.Imputation ";
                $query_str = $query_str . "FROM ( ";
                $query_str = $query_str . "    SELECT AM.Admixture_Group, AM.Group, AM.Country, AM.State, ";
                $query_str = $query_str . "    GD.Accession, AM.TAIR_Accession, AM.Name, ";
                $query_str = $query_str . "    GD.Gene, GD.Chromosome, ";
                $query_str = $query_str . "    GROUP_CONCAT(GD.Position SEPARATOR ' ') AS Position, ";
                $query_str = $query_str . "    GROUP_CONCAT(GD.Genotype SEPARATOR ' ') AS Genotype, ";
                $query_str = $query_str . "    GROUP_CONCAT(GD.Genotype_Description SEPARATOR ' ') AS Genotype_Description, ";
                $query_str = $query_str . "    GROUP_CONCAT(GD.Imputation SEPARATOR ' ') AS Imputation ";
                $query_str = $query_str . "    FROM ( ";
                $query_str = $query_str . "        SELECT G.Chromosome, G.Position, G.Accession, GFF.Gene, G.Genotype, ";
                $query_str = $query_str . "        CONCAT_WS('|', G.Genotype, G.Functional_Effect, G.Amino_Acid_Change, G.Imputation) AS Genotype_Description, ";
                $query_str = $query_str . "        G.Imputation ";
                $query_str = $query_str . "        FROM ( ";
                $query_str = $query_str . "            SELECT Chromosome, Start, End, Name AS Gene ";
                $query_str = $query_str . "            FROM " . $db . "." . $gff_table . " ";
                $query_str = $query_str . "            WHERE Name IN ('" . $gene_array[$i] . "') ";
                $query_str = $query_str . "        ) AS GFF ";
                $query_str = $query_str . "        INNER JOIN " . $db . ".act_" . $dataset . "_" . $temp_gene_result_arr[0]->Chromosome . " AS G ";
                $query_str = $query_str . "        ON (G.Chromosome = GFF.Chromosome) AND (G.Position >= GFF.Start) AND (G.Position <= GFF.End) ";
                $query_str = $query_str . "        ORDER BY G.Position ";
                $query_str = $query_str . "    ) AS GD ";
                $query_str = $query_str . "    LEFT JOIN " . $db . "." . $accession_mapping_table . " AS AM ";
                $query_str = $query_str . "    ON AM.Accession = GD.Accession ";
                $query_str = $query_str . "    GROUP BY AM.Admixture_Group, AM.Group, AM.Country, AM.State, GD.Accession, AM.TAIR_Accession, AM.Name, GD.Gene, GD.Chromosome ";
                $query_str = $query_str . ") AS ACD ";
                $query_str = $query_str . "ORDER BY ACD.Gene; ";
            } elseif ($organism == "Osativa") {
                // Generate SQL string
                $query_str = "SELECT ";
                $query_str = $query_str . "ACD.Subpopulation, ACD.Country, ";
                $query_str = $query_str . "ACD.Accession, ";
                $query_str = $query_str . "ACD.Gene, ACD.Chromosome, ACD.Position, ACD.Genotype, ACD.Genotype_Description, ACD.Imputation ";
                $query_str = $query_str . "FROM ( ";
                $query_str = $query_str . "    SELECT AM.Subpopulation, AM.Country, ";
                $query_str = $query_str . "    GD.Accession, ";
                $query_str = $query_str . "    GD.Gene, GD.Chromosome, ";
                $query_str = $query_str . "    GROUP_CONCAT(GD.Position SEPARATOR ' ') AS Position, ";
                $query_str = $query_str . "    GROUP_CONCAT(GD.Genotype SEPARATOR ' ') AS Genotype, ";
                $query_str = $query_str . "    GROUP_CONCAT(GD.Genotype_Description SEPARATOR ' ') AS Genotype_Description, ";
                $query_str = $query_str . "    GROUP_CONCAT(GD.Imputation SEPARATOR ' ') AS Imputation ";
                $query_str = $query_str . "    FROM ( ";
                $query_str = $query_str . "        SELECT G.Chromosome, G.Position, G.Accession, GFF.Gene, G.Genotype, ";
                $query_str = $query_str . "        CONCAT_WS('|', G.Genotype, G.Functional_Effect, G.Amino_Acid_Change, G.Imputation) AS Genotype_Description, ";
                $query_str = $query_str . "        G.Imputation ";
                $query_str = $query_str . "        FROM ( ";
                $query_str = $query_str . "            SELECT Chromosome, Start, End, Name AS Gene ";
                $query_str = $query_str . "            FROM " . $db . "." . $gff_table . " ";
                $query_str = $query_str . "            WHERE Name IN ('" . $gene_array[$i] . "') ";
                $query_str = $query_str . "        ) AS GFF ";
                $query_str = $query_str . "        INNER JOIN " . $db . ".act_" . $dataset . "_" . $temp_gene_result_arr[0]->Chromosome . " AS G ";
                $query_str = $query_str . "        ON (G.Chromosome = GFF.Chromosome) AND (G.Position >= GFF.Start) AND (G.Position <= GFF.End) ";
                $query_str = $query_str . "        ORDER BY G.Position ";
                $query_str = $query_str . "    ) AS GD ";
                $query_str = $query_str . "    LEFT JOIN " . $db . "." . $accession_mapping_table . " AS AM ";
                $query_str = $query_str . "    ON AM.Accession = GD.Accession ";
                $query_str = $query_str . "    GROUP BY AM.Subpopulation, AM.Country, GD.Accession, GD.Gene, GD.Chromosome ";
                $query_str = $query_str . ") AS ACD ";
                $query_str = $query_str . "ORDER BY ACD.Gene; ";
            }

            $result_arr = DB::connection($db)->select($query_str);

            if (!isset($allele_catalog_result_arr)){
                $allele_catalog_result_arr = (array) $result_arr;
            } else {
                $allele_catalog_result_arr = array_merge($allele_catalog_result_arr, (array) $result_arr);
            }

        }

        for ($i = 0; $i < count($allele_catalog_result_arr); $i++) {
            if (preg_match("/\+/i", $allele_catalog_result_arr[$i]->Imputation)) {
                $allele_catalog_result_arr[$i]->Imputation = "+";
            } else{
                $allele_catalog_result_arr[$i]->Imputation = "";
            }
        }

        return json_encode($allele_catalog_result_arr);
    }


    public function ViewAllByAccessionsAndGenePage(Request $request, $organism)
    {
        $admin_db_wapper = new DBAdminWrapperClass;

        // Database
        $db = "KBC_" . $organism;

        $query_str = "SET SESSION group_concat_max_len = 1000000; ";
        $set_group_concat_max_len_result = DB::connection($db)->select($query_str);

        $dataset = $request->dataset_2;
        $gene = $request->gene_2;
        $accession = $request->accession_2;

        if (is_string($accession)) {
            $accession_array = preg_split("/[;, \n]+/", $accession);
            for ($i = 0; $i < count($accession_array); $i++) {
                $accession_array[$i] = trim($accession_array[$i]);
            }
        } elseif (is_array($accession)) {
            $accession_array = $accession;
            for ($i = 0; $i < count($accession_array); $i++) {
                $accession_array[$i] = trim($accession_array[$i]);
            }
        }

        // Define key column
        if ($organism == "Zmays") {
            $key_column = "Improvement_Status";
        } elseif ($organism == "Athaliana") {
            $key_column = "Group";
        } elseif ($organism == "Osativa") {
            $key_column = "Subpopulation";
        }

        // Table names and datasets
        if ($organism == "Zmays") {
            $gff_table = "act_Maize_AGPv3_GFF";
            $accession_mapping_table = "act_Maize1210_Accession_Mapping";
        } elseif ($organism == "Athaliana") {
            $gff_table = "act_Arabidopsis_TAIR10_GFF";
            $accession_mapping_table = "act_Arabidopsis1135_Accession_Mapping";
        } elseif ($organism == "Osativa") {
            $gff_table = "act_Rice_Nipponbare_GFF";
            $accession_mapping_table = "act_Rice3000_Accession_Mapping";
        }

        try{
            // Generate SQL string
            $query_str = "SELECT Chromosome, Start, End, Name AS Gene ";
            $query_str = $query_str . "FROM " . $db . "." . $gff_table . " ";
            $query_str = $query_str . "WHERE Name IN ('" . $gene . "');";

            $gene_result_arr = DB::connection($db)->select($query_str);

            if ($organism == "Zmays") {
                // Generate SQL string
                $query_str = "SELECT ";
                $query_str = $query_str . "ACD.Kernel_Type, ACD.Improvement_Status, ACD.Country, ACD.State, ";
                $query_str = $query_str . "ACD.Accession, ACD.Panzea_Accession, ";
                $query_str = $query_str . "ACD.Gene, ACD.Chromosome, ACD.Position, ACD.Genotype, ACD.Genotype_Description, ACD.Imputation ";
                $query_str = $query_str . "FROM ( ";
                $query_str = $query_str . "    SELECT AM.Kernel_Type, AM.Improvement_Status, AM.Country, AM.State, ";
                $query_str = $query_str . "    GD.Accession, AM.Panzea_Accession, ";
                $query_str = $query_str . "    GD.Gene, GD.Chromosome, ";
                $query_str = $query_str . "    GROUP_CONCAT(GD.Position SEPARATOR ' ') AS Position, ";
                $query_str = $query_str . "    GROUP_CONCAT(GD.Genotype SEPARATOR ' ') AS Genotype, ";
                $query_str = $query_str . "    GROUP_CONCAT(GD.Genotype_Description SEPARATOR ' ') AS Genotype_Description, ";
                $query_str = $query_str . "    GROUP_CONCAT(GD.Imputation SEPARATOR ' ') AS Imputation ";
                $query_str = $query_str . "    FROM ( ";
                $query_str = $query_str . "        SELECT G.Chromosome, G.Position, G.Accession, GFF.Gene, G.Genotype, ";
                $query_str = $query_str . "        CONCAT_WS('|', G.Genotype, G.Functional_Effect, G.Amino_Acid_Change, G.Imputation) AS Genotype_Description, ";
                $query_str = $query_str . "        G.Imputation ";
                $query_str = $query_str . "        FROM ( ";
                $query_str = $query_str . "            SELECT Chromosome, Start, End, Name AS Gene ";
                $query_str = $query_str . "            FROM " . $db . "." . $gff_table . " ";
                $query_str = $query_str . "            WHERE Name IN ('" . $gene . "') ";
                $query_str = $query_str . "        ) AS GFF ";
                $query_str = $query_str . "        INNER JOIN " . $db . ".act_" . $dataset . "_" . $gene_result_arr[0]->Chromosome . " AS G ";
                $query_str = $query_str . "        ON (G.Chromosome = GFF.Chromosome) AND (G.Position >= GFF.Start) AND (G.Position <= GFF.End) ";
                $query_str = $query_str . "        ORDER BY G.Position ";
                $query_str = $query_str . "    ) AS GD ";
                $query_str = $query_str . "    LEFT JOIN " . $db . "." . $accession_mapping_table . " AS AM ";
                $query_str = $query_str . "    ON AM.Accession = GD.Accession ";
                $query_str = $query_str . "    GROUP BY AM.Kernel_Type, AM.Improvement_Status, AM.Country, AM.State, GD.Accession, AM.Panzea_Accession, GD.Gene, GD.Chromosome ";
                $query_str = $query_str . ") AS ACD ";
                $query_str = $query_str . "WHERE (ACD.Accession IN ('";
                for ($i = 0; $i < count($accession_array); $i++) {
                    if($i < (count($accession_array)-1)){
                        $query_str = $query_str . trim($accession_array[$i]) . "', '";
                    } elseif ($i == (count($accession_array)-1)) {
                        $query_str = $query_str . trim($accession_array[$i]);
                    }
                }
                $query_str = $query_str . "')) ";
                $query_str = $query_str . "OR (ACD.Panzea_Accession IN ('";
                for ($i = 0; $i < count($accession_array); $i++) {
                    if($i < (count($accession_array)-1)){
                        $query_str = $query_str . trim($accession_array[$i]) . "', '";
                    } elseif ($i == (count($accession_array)-1)) {
                        $query_str = $query_str . trim($accession_array[$i]);
                    }
                }
                $query_str = $query_str . "')) ";
                $query_str = $query_str . "ORDER BY ACD.Gene; ";

            } elseif ($organism == "Athaliana") {
                // Generate SQL string
                $query_str = "SELECT ";
                $query_str = $query_str . "ACD.Admixture_Group, ACD.Group, ACD.Country, ACD.State, ";
                $query_str = $query_str . "ACD.Accession, ACD.TAIR_Accession, ACD.Name, ";
                $query_str = $query_str . "ACD.Gene, ACD.Chromosome, ACD.Position, ACD.Genotype, ACD.Genotype_Description, ACD.Imputation ";
                $query_str = $query_str . "FROM ( ";
                $query_str = $query_str . "    SELECT AM.Admixture_Group, AM.Group, AM.Country, AM.State, ";
                $query_str = $query_str . "    GD.Accession, AM.TAIR_Accession, AM.Name, ";
                $query_str = $query_str . "    GD.Gene, GD.Chromosome, ";
                $query_str = $query_str . "    GROUP_CONCAT(GD.Position SEPARATOR ' ') AS Position, ";
                $query_str = $query_str . "    GROUP_CONCAT(GD.Genotype SEPARATOR ' ') AS Genotype, ";
                $query_str = $query_str . "    GROUP_CONCAT(GD.Genotype_Description SEPARATOR ' ') AS Genotype_Description, ";
                $query_str = $query_str . "    GROUP_CONCAT(GD.Imputation SEPARATOR ' ') AS Imputation ";
                $query_str = $query_str . "    FROM ( ";
                $query_str = $query_str . "        SELECT G.Chromosome, G.Position, G.Accession, GFF.Gene, G.Genotype, ";
                $query_str = $query_str . "        CONCAT_WS('|', G.Genotype, G.Functional_Effect, G.Amino_Acid_Change, G.Imputation) AS Genotype_Description, ";
                $query_str = $query_str . "        G.Imputation ";
                $query_str = $query_str . "        FROM ( ";
                $query_str = $query_str . "            SELECT Chromosome, Start, End, Name AS Gene ";
                $query_str = $query_str . "            FROM " . $db . "." . $gff_table . " ";
                $query_str = $query_str . "            WHERE Name IN ('" . $gene . "') ";
                $query_str = $query_str . "        ) AS GFF ";
                $query_str = $query_str . "        INNER JOIN " . $db . ".act_" . $dataset . "_" . $gene_result_arr[0]->Chromosome . " AS G ";
                $query_str = $query_str . "        ON (G.Chromosome = GFF.Chromosome) AND (G.Position >= GFF.Start) AND (G.Position <= GFF.End) ";
                $query_str = $query_str . "        ORDER BY G.Position ";
                $query_str = $query_str . "    ) AS GD ";
                $query_str = $query_str . "    LEFT JOIN " . $db . "." . $accession_mapping_table . " AS AM ";
                $query_str = $query_str . "    ON AM.Accession = GD.Accession ";
                $query_str = $query_str . "    GROUP BY AM.Admixture_Group, AM.Group, AM.Country, AM.State, GD.Accession, AM.TAIR_Accession, AM.Name, GD.Gene, GD.Chromosome ";
                $query_str = $query_str . ") AS ACD ";
                $query_str = $query_str . "WHERE (ACD.Accession IN ('";
                for ($i = 0; $i < count($accession_array); $i++) {
                    if($i < (count($accession_array)-1)){
                        $query_str = $query_str . trim($accession_array[$i]) . "', '";
                    } elseif ($i == (count($accession_array)-1)) {
                        $query_str = $query_str . trim($accession_array[$i]);
                    }
                }
                $query_str = $query_str . "')) ";
                $query_str = $query_str . "OR (ACD.TAIR_Accession IN ('";
                for ($i = 0; $i < count($accession_array); $i++) {
                    if($i < (count($accession_array)-1)){
                        $query_str = $query_str . trim($accession_array[$i]) . "', '";
                    } elseif ($i == (count($accession_array)-1)) {
                        $query_str = $query_str . trim($accession_array[$i]);
                    }
                }
                $query_str = $query_str . "')) ";
                $query_str = $query_str . "OR (ACD.Name IN ('";
                for ($i = 0; $i < count($accession_array); $i++) {
                    if($i < (count($accession_array)-1)){
                        $query_str = $query_str . trim($accession_array[$i]) . "', '";
                    } elseif ($i == (count($accession_array)-1)) {
                        $query_str = $query_str . trim($accession_array[$i]);
                    }
                }
                $query_str = $query_str . "')) ";
                $query_str = $query_str . "ORDER BY ACD.Gene; ";
            } elseif ($organism == "Osativa") {
                // Generate SQL string
                $query_str = "SELECT ";
                $query_str = $query_str . "ACD.Subpopulation, ACD.Country, ";
                $query_str = $query_str . "ACD.Accession, ";
                $query_str = $query_str . "ACD.Gene, ACD.Chromosome, ACD.Position, ACD.Genotype, ACD.Genotype_Description, ACD.Imputation ";
                $query_str = $query_str . "FROM ( ";
                $query_str = $query_str . "    SELECT AM.Subpopulation, AM.Country, ";
                $query_str = $query_str . "    GD.Accession, ";
                $query_str = $query_str . "    GD.Gene, GD.Chromosome, ";
                $query_str = $query_str . "    GROUP_CONCAT(GD.Position SEPARATOR ' ') AS Position, ";
                $query_str = $query_str . "    GROUP_CONCAT(GD.Genotype SEPARATOR ' ') AS Genotype, ";
                $query_str = $query_str . "    GROUP_CONCAT(GD.Genotype_Description SEPARATOR ' ') AS Genotype_Description, ";
                $query_str = $query_str . "    GROUP_CONCAT(GD.Imputation SEPARATOR ' ') AS Imputation ";
                $query_str = $query_str . "    FROM ( ";
                $query_str = $query_str . "        SELECT G.Chromosome, G.Position, G.Accession, GFF.Gene, G.Genotype, ";
                $query_str = $query_str . "        CONCAT_WS('|', G.Genotype, G.Functional_Effect, G.Amino_Acid_Change, G.Imputation) AS Genotype_Description, ";
                $query_str = $query_str . "        G.Imputation ";
                $query_str = $query_str . "        FROM ( ";
                $query_str = $query_str . "            SELECT Chromosome, Start, End, Name AS Gene ";
                $query_str = $query_str . "            FROM " . $db . "." . $gff_table . " ";
                $query_str = $query_str . "            WHERE Name IN ('" . $gene . "') ";
                $query_str = $query_str . "        ) AS GFF ";
                $query_str = $query_str . "        INNER JOIN " . $db . ".act_" . $dataset . "_" . $gene_result_arr[0]->Chromosome . " AS G ";
                $query_str = $query_str . "        ON (G.Chromosome = GFF.Chromosome) AND (G.Position >= GFF.Start) AND (G.Position <= GFF.End) ";
                $query_str = $query_str . "        ORDER BY G.Position ";
                $query_str = $query_str . "    ) AS GD ";
                $query_str = $query_str . "    LEFT JOIN " . $db . "." . $accession_mapping_table . " AS AM ";
                $query_str = $query_str . "    ON AM.Accession = GD.Accession ";
                $query_str = $query_str . "    GROUP BY AM.Subpopulation, AM.Country, GD.Accession, GD.Gene, GD.Chromosome ";
                $query_str = $query_str . ") AS ACD ";
                $query_str = $query_str . "WHERE (ACD.Accession IN ('";
                for ($i = 0; $i < count($accession_array); $i++) {
                    if($i < (count($accession_array)-1)){
                        $query_str = $query_str . trim($accession_array[$i]) . "', '";
                    } elseif ($i == (count($accession_array)-1)) {
                        $query_str = $query_str . trim($accession_array[$i]);
                    }
                }
                $query_str = $query_str . "')) ";
                $query_str = $query_str . "ORDER BY ACD.Gene; ";
            }

            $result_arr = DB::connection($db)->select($query_str);
        } catch (\Exception $e) {
            $result_arr = (object)Array();
        }

        // Package variables that need to go to the view
        $info = [
            'organism' => $organism,
            'dataset' => $dataset,
            'gene' => $gene,
            'accession_array' => $accession_array,
            'result_arr' => $result_arr
        ];

        // Return to view
        return view('system/tools/AlleleCatalogTool2/viewAllByAccessionsAndGene')->with('info', $info);
    }


    public function QueryAllByAccessionsAndGene(Request $request, $organism) {

        // Database
        $db = "KBC_" . $organism;

        $query_str = "SET SESSION group_concat_max_len = 1000000; ";
        $set_group_concat_max_len_result = DB::connection($db)->select($query_str);

        $dataset = $request->Dataset;
        $gene = $request->Gene;
        $accession = $request->Accession_Array;

        if (is_string($accession)) {
            $accession_array = preg_split("/[;, \n]+/", $accession);
            for ($i = 0; $i < count($accession_array); $i++) {
                $accession_array[$i] = trim($accession_array[$i]);
            }
        } elseif (is_array($accession)) {
            $accession_array = $accession;
            for ($i = 0; $i < count($accession_array); $i++) {
                $accession_array[$i] = trim($accession_array[$i]);
            }
        }

        // Define key column
        if ($organism == "Zmays") {
            $key_column = "Improvement_Status";
        } elseif ($organism == "Athaliana") {
            $key_column = "Group";
        } elseif ($organism == "Osativa") {
            $key_column = "Subpopulation";
        }

        // Table names and datasets
        if ($organism == "Zmays") {
            $gff_table = "act_Maize_AGPv3_GFF";
            $accession_mapping_table = "act_Maize1210_Accession_Mapping";
        } elseif ($organism == "Athaliana") {
            $gff_table = "act_Arabidopsis_TAIR10_GFF";
            $accession_mapping_table = "act_Arabidopsis1135_Accession_Mapping";
        } elseif ($organism == "Osativa") {
            $gff_table = "act_Rice_Nipponbare_GFF";
            $accession_mapping_table = "act_Rice3000_Accession_Mapping";
        }

        // Generate SQL string
        $query_str = "SELECT Chromosome, Start, End, Name AS Gene ";
        $query_str = $query_str . "FROM " . $db . "." . $gff_table . " ";
        $query_str = $query_str . "WHERE Name IN ('" . $gene . "');";

        $gene_result_arr = DB::connection($db)->select($query_str);


        if ($organism == "Zmays") {
            // Generate SQL string
            $query_str = "SELECT ";
            $query_str = $query_str . "ACD.Kernel_Type, ACD.Improvement_Status, ACD.Country, ACD.State, ";
            $query_str = $query_str . "ACD.Accession, ACD.Panzea_Accession, ";
            $query_str = $query_str . "ACD.Gene, ACD.Chromosome, ACD.Position, ACD.Genotype, ACD.Genotype_Description, ACD.Imputation ";
            $query_str = $query_str . "FROM ( ";
            $query_str = $query_str . "    SELECT AM.Kernel_Type, AM.Improvement_Status, AM.Country, AM.State, ";
            $query_str = $query_str . "    GD.Accession, AM.Panzea_Accession, ";
            $query_str = $query_str . "    GD.Gene, GD.Chromosome, ";
            $query_str = $query_str . "    GROUP_CONCAT(GD.Position SEPARATOR ' ') AS Position, ";
            $query_str = $query_str . "    GROUP_CONCAT(GD.Genotype SEPARATOR ' ') AS Genotype, ";
            $query_str = $query_str . "    GROUP_CONCAT(GD.Genotype_Description SEPARATOR ' ') AS Genotype_Description, ";
            $query_str = $query_str . "    GROUP_CONCAT(GD.Imputation SEPARATOR ' ') AS Imputation ";
            $query_str = $query_str . "    FROM ( ";
            $query_str = $query_str . "        SELECT G.Chromosome, G.Position, G.Accession, GFF.Gene, G.Genotype, ";
            $query_str = $query_str . "        CONCAT_WS('|', G.Genotype, G.Functional_Effect, G.Amino_Acid_Change, G.Imputation) AS Genotype_Description, ";
            $query_str = $query_str . "        G.Imputation ";
            $query_str = $query_str . "        FROM ( ";
            $query_str = $query_str . "            SELECT Chromosome, Start, End, Name AS Gene ";
            $query_str = $query_str . "            FROM " . $db . "." . $gff_table . " ";
            $query_str = $query_str . "            WHERE Name IN ('" . $gene . "') ";
            $query_str = $query_str . "        ) AS GFF ";
            $query_str = $query_str . "        INNER JOIN " . $db . ".act_" . $dataset . "_" . $gene_result_arr[0]->Chromosome . " AS G ";
            $query_str = $query_str . "        ON (G.Chromosome = GFF.Chromosome) AND (G.Position >= GFF.Start) AND (G.Position <= GFF.End) ";
            $query_str = $query_str . "        ORDER BY G.Position ";
            $query_str = $query_str . "    ) AS GD ";
            $query_str = $query_str . "    LEFT JOIN " . $db . "." . $accession_mapping_table . " AS AM ";
            $query_str = $query_str . "    ON AM.Accession = GD.Accession ";
            $query_str = $query_str . "    GROUP BY AM.Kernel_Type, AM.Improvement_Status, AM.Country, AM.State, GD.Accession, AM.Panzea_Accession, GD.Gene, GD.Chromosome ";
            $query_str = $query_str . ") AS ACD ";
            $query_str = $query_str . "WHERE (ACD.Accession IN ('";
            for ($i = 0; $i < count($accession_array); $i++) {
                if($i < (count($accession_array)-1)){
                    $query_str = $query_str . trim($accession_array[$i]) . "', '";
                } elseif ($i == (count($accession_array)-1)) {
                    $query_str = $query_str . trim($accession_array[$i]);
                }
            }
            $query_str = $query_str . "')) ";
            $query_str = $query_str . "OR (ACD.Panzea_Accession IN ('";
            for ($i = 0; $i < count($accession_array); $i++) {
                if($i < (count($accession_array)-1)){
                    $query_str = $query_str . trim($accession_array[$i]) . "', '";
                } elseif ($i == (count($accession_array)-1)) {
                    $query_str = $query_str . trim($accession_array[$i]);
                }
            }
            $query_str = $query_str . "')) ";
            $query_str = $query_str . "ORDER BY ACD.Gene; ";

        } elseif ($organism == "Athaliana") {
            // Generate SQL string
            $query_str = "SELECT ";
            $query_str = $query_str . "ACD.Admixture_Group, ACD.Group, ACD.Country, ACD.State, ";
            $query_str = $query_str . "ACD.Accession, ACD.TAIR_Accession, ACD.Name, ";
            $query_str = $query_str . "ACD.Gene, ACD.Chromosome, ACD.Position, ACD.Genotype, ACD.Genotype_Description, ACD.Imputation ";
            $query_str = $query_str . "FROM ( ";
            $query_str = $query_str . "    SELECT AM.Admixture_Group, AM.Group, AM.Country, AM.State, ";
            $query_str = $query_str . "    GD.Accession, AM.TAIR_Accession, AM.Name, ";
            $query_str = $query_str . "    GD.Gene, GD.Chromosome, ";
            $query_str = $query_str . "    GROUP_CONCAT(GD.Position SEPARATOR ' ') AS Position, ";
            $query_str = $query_str . "    GROUP_CONCAT(GD.Genotype SEPARATOR ' ') AS Genotype, ";
            $query_str = $query_str . "    GROUP_CONCAT(GD.Genotype_Description SEPARATOR ' ') AS Genotype_Description, ";
            $query_str = $query_str . "    GROUP_CONCAT(GD.Imputation SEPARATOR ' ') AS Imputation ";
            $query_str = $query_str . "    FROM ( ";
            $query_str = $query_str . "        SELECT G.Chromosome, G.Position, G.Accession, GFF.Gene, G.Genotype, ";
            $query_str = $query_str . "        CONCAT_WS('|', G.Genotype, G.Functional_Effect, G.Amino_Acid_Change, G.Imputation) AS Genotype_Description, ";
            $query_str = $query_str . "        G.Imputation ";
            $query_str = $query_str . "        FROM ( ";
            $query_str = $query_str . "            SELECT Chromosome, Start, End, Name AS Gene ";
            $query_str = $query_str . "            FROM " . $db . "." . $gff_table . " ";
            $query_str = $query_str . "            WHERE Name IN ('" . $gene . "') ";
            $query_str = $query_str . "        ) AS GFF ";
            $query_str = $query_str . "        INNER JOIN " . $db . ".act_" . $dataset . "_" . $gene_result_arr[0]->Chromosome . " AS G ";
            $query_str = $query_str . "        ON (G.Chromosome = GFF.Chromosome) AND (G.Position >= GFF.Start) AND (G.Position <= GFF.End) ";
            $query_str = $query_str . "        ORDER BY G.Position ";
            $query_str = $query_str . "    ) AS GD ";
            $query_str = $query_str . "    LEFT JOIN " . $db . "." . $accession_mapping_table . " AS AM ";
            $query_str = $query_str . "    ON AM.Accession = GD.Accession ";
            $query_str = $query_str . "    GROUP BY AM.Admixture_Group, AM.Group, AM.Country, AM.State, GD.Accession, AM.TAIR_Accession, AM.Name, GD.Gene, GD.Chromosome ";
            $query_str = $query_str . ") AS ACD ";
            $query_str = $query_str . "WHERE (ACD.Accession IN ('";
            for ($i = 0; $i < count($accession_array); $i++) {
                if($i < (count($accession_array)-1)){
                    $query_str = $query_str . trim($accession_array[$i]) . "', '";
                } elseif ($i == (count($accession_array)-1)) {
                    $query_str = $query_str . trim($accession_array[$i]);
                }
            }
            $query_str = $query_str . "')) ";
            $query_str = $query_str . "OR (ACD.TAIR_Accession IN ('";
            for ($i = 0; $i < count($accession_array); $i++) {
                if($i < (count($accession_array)-1)){
                    $query_str = $query_str . trim($accession_array[$i]) . "', '";
                } elseif ($i == (count($accession_array)-1)) {
                    $query_str = $query_str . trim($accession_array[$i]);
                }
            }
            $query_str = $query_str . "')) ";
            $query_str = $query_str . "OR (ACD.Name IN ('";
            for ($i = 0; $i < count($accession_array); $i++) {
                if($i < (count($accession_array)-1)){
                    $query_str = $query_str . trim($accession_array[$i]) . "', '";
                } elseif ($i == (count($accession_array)-1)) {
                    $query_str = $query_str . trim($accession_array[$i]);
                }
            }
            $query_str = $query_str . "')) ";
            $query_str = $query_str . "ORDER BY ACD.Gene; ";
        } elseif ($organism == "Osativa") {
            // Generate SQL string
            $query_str = "SELECT ";
            $query_str = $query_str . "ACD.Subpopulation, ACD.Country, ";
            $query_str = $query_str . "ACD.Accession, ";
            $query_str = $query_str . "ACD.Gene, ACD.Chromosome, ACD.Position, ACD.Genotype, ACD.Genotype_Description, ACD.Imputation ";
            $query_str = $query_str . "FROM ( ";
            $query_str = $query_str . "    SELECT AM.Subpopulation, AM.Country, ";
            $query_str = $query_str . "    GD.Accession, ";
            $query_str = $query_str . "    GD.Gene, GD.Chromosome, ";
            $query_str = $query_str . "    GROUP_CONCAT(GD.Position SEPARATOR ' ') AS Position, ";
            $query_str = $query_str . "    GROUP_CONCAT(GD.Genotype SEPARATOR ' ') AS Genotype, ";
            $query_str = $query_str . "    GROUP_CONCAT(GD.Genotype_Description SEPARATOR ' ') AS Genotype_Description, ";
            $query_str = $query_str . "    GROUP_CONCAT(GD.Imputation SEPARATOR ' ') AS Imputation ";
            $query_str = $query_str . "    FROM ( ";
            $query_str = $query_str . "        SELECT G.Chromosome, G.Position, G.Accession, GFF.Gene, G.Genotype, ";
            $query_str = $query_str . "        CONCAT_WS('|', G.Genotype, G.Functional_Effect, G.Amino_Acid_Change, G.Imputation) AS Genotype_Description, ";
            $query_str = $query_str . "        G.Imputation ";
            $query_str = $query_str . "        FROM ( ";
            $query_str = $query_str . "            SELECT Chromosome, Start, End, Name AS Gene ";
            $query_str = $query_str . "            FROM " . $db . "." . $gff_table . " ";
            $query_str = $query_str . "            WHERE Name IN ('" . $gene . "') ";
            $query_str = $query_str . "        ) AS GFF ";
            $query_str = $query_str . "        INNER JOIN " . $db . ".act_" . $dataset . "_" . $gene_result_arr[0]->Chromosome . " AS G ";
            $query_str = $query_str . "        ON (G.Chromosome = GFF.Chromosome) AND (G.Position >= GFF.Start) AND (G.Position <= GFF.End) ";
            $query_str = $query_str . "        ORDER BY G.Position ";
            $query_str = $query_str . "    ) AS GD ";
            $query_str = $query_str . "    LEFT JOIN " . $db . "." . $accession_mapping_table . " AS AM ";
            $query_str = $query_str . "    ON AM.Accession = GD.Accession ";
            $query_str = $query_str . "    GROUP BY AM.Subpopulation, AM.Country, GD.Accession, GD.Gene, GD.Chromosome ";
            $query_str = $query_str . ") AS ACD ";
            $query_str = $query_str . "WHERE (ACD.Accession IN ('";
            for ($i = 0; $i < count($accession_array); $i++) {
                if($i < (count($accession_array)-1)){
                    $query_str = $query_str . trim($accession_array[$i]) . "', '";
                } elseif ($i == (count($accession_array)-1)) {
                    $query_str = $query_str . trim($accession_array[$i]);
                }
            }
            $query_str = $query_str . "')) ";
            $query_str = $query_str . "ORDER BY ACD.Gene; ";
        }

        $result_arr = DB::connection($db)->select($query_str);

        for ($i = 0; $i < count($result_arr); $i++) {
            if (preg_match("/\+/i", $result_arr[$i]->Imputation)) {
                $result_arr[$i]->Imputation = "+";
            } else{
                $result_arr[$i]->Imputation = "";
            }
        }

        return json_encode($result_arr);
    }

}