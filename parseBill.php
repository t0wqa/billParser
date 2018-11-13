<?php
/**
 * Created by PhpStorm.
 * User: t0wqa
 * Date: 13.11.2018
 * Time: 17:46
 */

require_once 'phpQuery-onefile.php';

/**
 * Class BillParser
 */
class BillParser
{

    private $document;

    private $data;

    private $connection;

    /**
     * BillParser constructor.
     * @param PDO $connection
     */
    public function __construct(\PDO $connection = null)
    {
        $html = file_get_contents('https://www.bills.ru/news/');
        $this->document = phpQuery::newDocument($html);
        $this->connection = $connection;
    }

    /**
     * @return array
     */
    public function getData() : array
    {
        return $this->data;
    }

    /**
     * @return void
     */
    public function parse() : void
    {
        $this->parseDateTime();
        $this->parseContent();
    }

    /**
     * @throws Exception
     */
    public function save() : void
    {
        if (empty($this->data)) {
            throw new Exception('No data yet parsed!');
        }

        if (null === $this->connection) {
            throw new Exception('No DB connection');
        }

        foreach ($this->data as $item) {
            $stmt = $this->connection->prepare("REPLACE INTO zf_test.bills_ru_events (`date`, `title`, `link`) VALUES (:date, :title, :link)");

            $stmt->execute([
                'date' => $item['date'],
                'title' => $item['title'],
                'link' => $item['link']
            ]);
        }
    }

    /**
     * @return void
     */
    private function parseDateTime() : void
    {
        $newsItemHeaders = $this->document->find('.news_item_header');

        $counter = 0;

        foreach ($newsItemHeaders as $newsItemHeader) {
            $newsItemHeader = pq($newsItemHeader);

            $newsItemTRS = $newsItemHeader->find('tr');
            $dateTimeTR = pq($newsItemTRS->get(count($newsItemTRS) - 1));

            $dateTime = $dateTimeTR->find('.news_feed_item_date span')->attr('title');
            $this->data[$counter]['date'] = (new DateTime($dateTime))->format('Y-m-d h:m:s');

            $counter++;
        }
    }

    /**
     * @return void
     */
    private function parseContent() : void
    {
        $newsTitles = $this->document->find('.news_title a');

        $counter = 0;

        foreach ($newsTitles as $newsTitle) {
            $newsTitle = pq($newsTitle);

            $title = $newsTitle->attr('title');
            $link = $newsTitle->attr('href');

            $this->data[$counter]['title'] = $title;
            $this->data[$counter]['link'] = $link;

            $counter++;
        }
    }

}

$parser = new BillParser(new PDO(
    'mysql:dbname=zf_test;host=localhost',
    'foo',
    'bar'
));

$parser->parse();
print_r($parser->getData());
$parser->save();

