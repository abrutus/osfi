<?php
/**
 * Date: 2/12/15
 * Time: 12:00 PM
 * Andre Brutus
 */

namespace WePay\Command;


use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use WePay\Strategy\ExactName;
use WePay\Strategy\Permute;
use WePay\Structure\CsvStructure;
use WePay\Structure\Index;
use WePay\Utility\PersistAzure;

class PersistFile  extends Command
{
    private $SdnList = [];
    private $counter = 0;
    protected function configure()
    {
        $this
            ->setName('ofac:persist')
            ->setDescription('Update the SDN list')
            ->addArgument(
                'letter',
                InputArgument::OPTIONAL,
                'What letter do you want to persist'
            )
        ;
    }


    protected function execute(InputInterface $input, OutputInterface $output) {
        $handle_fd  = [];
        $handles    = array_merge(range('A', 'Z'), range(0, 9));
        $letter = $input->getArgument('letter');
        if($letter) {
            $handles = [substr($letter, 0, 1)];
        }
        foreach ($handles as $letter) {
            $handle_fd[$letter] = fopen("./temp/" . $letter . ".txt", "r");
        }
        $this->azure($handle_fd);
    }
    protected function azure(&$handles) {
        $az = new PersistAzure();
        foreach($handles as $k => $handle) {
            echo "$k \n";
            rewind($handle);
            while($row = fgetcsv($handle)) {
                $az->add($k, $row[1], [$row[0]]);
            }
            $az->flush();
        }
        echo $az->total . " total records ran through add\n";
    }
}