<?php
/**
 * Created by PhpStorm.
 * User: patrickclover
 * Date: 18/04/2017
 * Time: 20:46
 */

namespace App\Controllers\Integrations\Uploads;

use App\Models\FileUploads;
use App\Utils\Http;
use App\Utils\Strings;
use Doctrine\ORM\EntityManager;
use Slim\Http\Response;
use Slim\Http\Request;

class _UploadStorageController extends _UploadsController
{
    protected $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
        parent::__construct();
    }

    public function exportCSVRoute(Request $request, Response $response)
    {
        $fileName = $request->getAttribute('filename');
        $send     = $this->exportCSV($fileName);

        if (isset($send['content'])) {
            return $response->withHeader('Content-Type', 'application/force-download')
                ->withHeader('Content-Type', 'application/octet-stream')
                ->withHeader('Content-Type', 'application/download')
                ->withHeader('Content-Description', 'File Transfer')
                ->withHeader('Content-Transfer-Encoding', 'binary')
                ->withHeader('Content-Disposition', 'attachment; filename="' . basename($fileName . '.csv') . '"')
                ->withHeader('Expires', '0')
                ->withHeader('Cache-Control', 'must-revalidate, post-check=0, pre-check=0')
                ->withHeader('Pragma', 'public')
                ->write($send['content']);
        }

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    private function exportCSV($filename)
    {
        $find = $this->em->getRepository(FileUploads::class)->findOneBy([
            'filename' => $filename . '.csv',
            'deleted'  => false
        ]);
        if (is_object($find)) {
            $key = $find->path . '/' . $find->filename;

            $stream = $this->getFile($key);

            return [
                'content' => $stream
            ];
        }

        return Http::status(204, 'FILE_NOT_FOUND');
    }

    public function saveFile($path, $kind, $url)
    {
        $createFileRef = new FileUploads($kind, $path, str_replace('/', '', substr($url, strrpos($url, '/'))), $url);
        $this->em->persist($createFileRef);
        $this->em->flush();

        return $createFileRef->getArrayCopy();
    }

    public function checkFile($path, $kind, $loadFile = false)
    {
        $finder = $this->em->getRepository(FileUploads::class)->findOneBy([
            'path'    => $path,
            'kind'    => $kind,
            'deleted' => false
        ], [
            'id' => 'DESC'
        ]);

        if (!is_null($finder)) {
            if ($loadFile === true) {
                return Http::status(200, $this->loadFile($finder->url));
            }

            return Http::status(200, $finder->filename);
        }

        return Http::status(404);
    }

    public function generateCsv($headers, $data, $dir, $kind)
    {
        $guid      = Strings::idGenerator('fle');
        $extension = 'csv';
        $filename  = $guid . '.' . $extension;

        /**
         * OPEN A NEW FILE WITH A GUID FILENAME
         */
        $newCSV = fopen($filename, 'w');
        /**
         * ADD HEADERS TO CSV FILE,
         * TREATED LIKE A ROW,
         * THE ORDER OF THESE NEEDS TO MATCH THE $data ARRAY. #EXCEL
         */
        fputcsv($newCSV, $headers);
        foreach ($data as $arrayKey => $pieces) {
            foreach ($pieces as $key => $piece) {
                /**
                 * CHECK IF PIECE HAS ANY DateTimes
                 */
                if ($piece instanceof \DateTime) {
                    /**
                     * CONVERT THEM TO STRINGS
                     */
                    $pieces[$key] = $piece->format('Y-m-d H:i:s');
                }
                /**
                 * THERE MAYBE THINGS DEEPLY NESTED,
                 * THESE WILL FAIL AS fputcsv CAN ONLY HANDLE STRINGS
                 */
                if (is_array($piece)) {
                    if (isset($piece['date'])) {
                        $dateTime     = new \DateTime($piece['date']);
                        $pieces[$key] = $dateTime->format('Y-m-d H:i:s');
                    }
                }
            }
            fputcsv($newCSV, $pieces);
        }

        fclose($newCSV);

        /**
         * UPLOAD TO S3 BY READING FROM THE TEMPORARY FILE
         */

        $upload = $this->s3->putObject([
            'Body'   => fopen($filename, 'r+'),
            'Key'    => $dir . '/' . $filename,
            'Bucket' => $this->bucket,
            'ACL'    => 'public-read'
        ]);

        /**
         * GET RID OF THE TEMPORARY FILE NOW ITS IN S3
         */

        unlink($filename);
        $fullpath = $upload->get('ObjectURL');
        $this->saveFile($dir, $kind, $fullpath);

        return $fullpath;
    }
}
