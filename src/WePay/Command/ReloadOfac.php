<?php
/**
 * Date: 2/12/15
 * Time: 12:00 PM
 * Andre Brutus
 */

namespace WePay\Command;


use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use WePay\Strategy\ExactName;
use WePay\Strategy\Permute;
use WePay\Structure\CsvStructure;
use WePay\Structure\Index;
use WePay\Utility\PersistAzure;

class ReloadOfac  extends Command
{
    private $SdnList = [];
    private $counter = 0;
    protected function configure()
    {
        $this
            ->setName('ofac:update')
            ->setDescription('Update the SDN list')
        ;
    }
    private function  persistIndex(CsvStructure $sdn, array $index, array $handles) {
        foreach($index as $value) {
            $first_letter = substr($value, 0, 1);
            $handle = &$handles[$first_letter];
            $field = [$sdn->ent_num, $value];
            fputcsv($handle, $field);
            $this->counter++;
        }
    }


    protected function execute(InputInterface $input, OutputInterface $output) {
        $class_files = [
            'WePay\Structure\Sdn' => [
                "http://www.treasury.gov/ofac/downloads/sdn.csv",
                "http://www.treasury.gov/ofac/downloads/consolidated/cons_prim.csv"
            ],
            'WePay\Structure\Alt' => [
                "http://www.treasury.gov/ofac/downloads/alt.csv",
                "http://www.treasury.gov/ofac/downloads/consolidated/cons_alt.csv"
            ]
        ];
        $handle_fd  = [];
        $handles    = array_merge(range('A', 'Z'), range(0, 9));
        foreach ($handles as $letter) {
            $handle_fd[$letter] = fopen("./temp/" . $letter . ".txt", "w+");
        }
        foreach($class_files as $class => $files) {
            foreach ($files as $file) {
                $csv_handle = fopen($file, 'r');
                while (!feof($csv_handle)) {
                    $csv_line   = fgetcsv($csv_handle);
                    $sdn        = new $class($csv_line);
                    $this->SdnList[$sdn->ent_num] = $csv_line;
                    $exact_name = new ExactName($sdn);
                    $permute    = new Permute($exact_name->getResult());
                    $index      = new Index($permute->getResult());
                    $this->persistIndex($sdn, $index->getIndices(), $handle_fd);
                }
            }
        }
        $this->azure($handle_fd);
    }
    protected function azure(&$handles) {
        $az = new PersistAzure();
        foreach($handles as $k => $handle) {
            echo "$k \n";
            rewind($handle);
            while($row = fgetcsv($handle)) {
                $az->add($k, $row[1], $this->SdnList[$row[0]]);
            }
            $az->flush();
        }
    }
}