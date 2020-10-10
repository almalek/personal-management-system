<?php

namespace App\Controller\Utils;

use App\Services\Files\FileTagger;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Form\Util\StringUtil;
use Symfony\Component\HttpFoundation\Response;

class Utils extends AbstractController {

    const FLASH_TYPE_SUCCESS = "success";
    const FLASH_TYPE_DANGER  = "danger";

    const TRUE_AS_STRING  = "true";
    const FALSE_AS_STRING = "false";

    /**
     * @param string $data
     * @return string
     */
    public static function unbase64(string $data) {
        return trim(htmlspecialchars_decode(base64_decode($data)));
    }

    /**
     * @param string $dir
     * @return bool
     */
    public static function removeFolderRecursively(string $dir) {
        $files = array_diff(scandir($dir), array('.','..'));
        foreach ($files as $file) {
            (is_dir("$dir/$file")) ? static::removeFolderRecursively("$dir/$file") : unlink("$dir/$file");
        }
        return rmdir($dir);
    }

    /**
     * Todo: move to files handling service/controller
     *
     * @param string $source
     * @param string $destination
     * @param FileTagger|null $file_tagger
     * @throws Exception
     */
    public static function copyFiles(string $source, string $destination, ?FileTagger $file_tagger = null) {
        $finder = new Finder();
        $finder->depth('==0');

        if (is_dir($source)) {

            $finder->files()->in($source);

            /**
             * @var $file SplFileInfo
             */
            foreach( $finder->files() as $file ){
                $filepath                   = $file->getPathname();
                $file_extension             = $file->getExtension();
                $filename_without_extension = $file->getFilenameWithoutExtension();

                $file_path_in_destination_folder = "{$destination}/{$filename_without_extension}.{$file_extension}";

                if( file_exists($file_path_in_destination_folder) ){
                    $curr_date_time     = new \DateTime();
                    $filename_date_time = $curr_date_time->format('Y_m_d_h_i_s');

                    $file_path_in_destination_folder = "{$destination}/{$filename_without_extension}.{$filename_date_time}.{$file_extension}";
                }

                copy($filepath, $file_path_in_destination_folder);

                if( !is_null($file_tagger) ){
                    $file_tagger->copyTagsFromPathToNewPath($filepath, $file_path_in_destination_folder);
                }
            }

        }else{
            copy($source, $destination);
        }

    }

    /**
     * @param Response $response
     * @return string
     */
    public static function getFlashTypeForRequest(Response $response){
        $flashType = ( $response->getStatusCode() === 200 ? self::FLASH_TYPE_SUCCESS : self::FLASH_TYPE_DANGER );
        return $flashType;
    }

    /**
     * Get one random element from array
     * @param array $array
     * @return mixed
     */
    public static function arrayGetRandom(array $array) {
        $index      = array_rand($array);
        $element    = $array[$index];

        return $element;
    }

    /**
     * @param array $data
     * @param int $count
     * @return array
     */
    public static function arrayGetNotRepeatingValuesCount(array $data, int $count) {

        $randoms = [];

        for($x = 0; $x <= $count; $x++) {

            $array_index = array_rand($data);
            $randoms[]   = $data[$array_index];

            unset($data[$array_index]);

            if( empty ($data) ) {
                break;
            }

        }

        return $randoms;
    }

    /**
     * This function will search for forms with names in @param array $keys_to_filter
     * This function should be used only when there are more than one forms in the request
     *  otherwise it will filter unwanted data
     * @param array $request_arrays
     * @return array
     * @see $keysToFilter and unset them in $request arrays
     */
    public static function filterRequestForms(array $keys_to_filter, array $request_arrays):array {

        foreach($keys_to_filter as $key){

            if( array_key_exists($key, $request_arrays) ){
                unset($request_arrays[$key]);
            }

        }

        return $request_arrays;
    }

    /**
     * @param string $class
     * @return string
     */
    public static function formClassToFormPrefix(string $class){
        return StringUtil::fqcnToBlockPrefix($class) ?: '';
    }

    /**
     * @return string
     */
    public static function randomHexColor() {
        return '#' . str_pad(dechex(mt_rand(0, 0xFFFFFF)), 6, '0', STR_PAD_LEFT);
    }

    /**
     * @param array $array
     * @return array
     */
    public static function arrayKeysMulti(array $array): array
    {
        $keys = array();

        foreach ($array as $key => $value) {
            $keys[] = $key;

            if (is_array($value)) {
                $keys = array_merge($keys, self::arrayKeysMulti($value));
            }
        }

        return $keys;
    }

    /**
     * @param array $array
     * @return string
     */
    public static function escapedDoubleQuoteJsonEncode(array $array): string
    {
        $json              = \GuzzleHttp\json_encode($array);
        $single_quote_json = str_replace('"','\"' , $json);

        return $single_quote_json;
    }

    /**
     * @param string|bool $value
     * @return bool
     * @throws Exception
     */
    public static function getBoolRepresentationOfBoolString($value): bool
    {
        if( is_bool($value) ){
            return $value;
        }elseif( is_string($value) ){

            $allowed_values = [
                self::TRUE_AS_STRING, self::FALSE_AS_STRING
            ];

            if( !in_array($value, $allowed_values) ){
                throw new Exception("Not a bool string");
            }

            return self::TRUE_AS_STRING === $value;
        }else{
            throw new \TypeError("Not allowed type: " . gettype($value) );
        }

    }

    /**
     * Returns the class name without namespace
     * @param string $class_with_namespace
     * @return string
     */
    public static function getClassBasename(string $class_with_namespace): string
    {
        $class_parts             = explode('\\', $class_with_namespace);
        $class_without_namespace = end($class_parts);
        return $class_without_namespace;
    }

    /**
     * Will round the given value to the nearest (LOWER/DOWN) value provided as second parameter, for example nearest 0.25
     * 1.0, 1.25, 1.5, 1,75 ....
     *
     * @param float $actual_value
     * @param float $round_to_repentance_of
     * @return float|int
     */
    public static function roundDownToAny(float $actual_value, float $round_to_repentance_of) {
        if( 0 === $actual_value ){
            return 0;
        }

        return floor($actual_value/$round_to_repentance_of) * $round_to_repentance_of;
    }

    /**
     * Will convert seconds to time based format
     * Example: 20:35:15
     *
     * @param int $seconds
     * @return string
     */
    public static function secondsToTimeFormat(int $seconds): string
    {
        $hours = floor($seconds / 3600);
        $mins  = floor($seconds / 60 % 60);
        $secs  = floor($seconds % 60);

        $time_format = sprintf('%02d:%02d:%02d', $hours, $mins, $secs);

        return $time_format;
    }
}
