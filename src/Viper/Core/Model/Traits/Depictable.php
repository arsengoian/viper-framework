<?php

namespace Viper\Core\Model\Traits;

use Viper\Core\Config;
use Viper\Support\Libs\DataCollection;
use Viper\Support\Libs\Util;
use WideImage\WideImage;


trait Depictable {

    // TODO add parameters

    final public function setImg(array $picture_arr, string $address) {
        $this -> set("img", self::registerImg($picture_arr, $address));
    }


    final public static function registerImg(array $picture_arr, string $name_appendix = '', bool $truename = TRUE): ?string {

        if (!$picture_arr || $picture_arr["name"] == "")
            return NULL;

        if ($picture_arr["error"] !== UPLOAD_ERR_OK)
            throw new ImageError("Internal server error: ".$picture_arr["error"]);

        $dbname = "/data/img/".$name_appendix.
            ($truename ? '___'.$picture_arr['name'] : '.'.pathinfo($picture_arr["name"], PATHINFO_EXTENSION));


        $root_dir = '/'.Config::get('ROOT_DIR');
        $target_filename = root().$root_dir.$dbname;
        $dir = dirname($target_filename);
        if (!file_exists($dir))
            Util::recursiveMkdir($dir);

        $check = getimagesize($picture_arr["tmp_name"]);
        if ($check === FALSE)
            throw new ImageError("File is not an image");

        $alform = array("image/jpeg", "image/pjpeg", "image/gif", "image/png");
        $skflg = FALSE;
        foreach ($alform as $type)
            if ($check["mime"] == $type)
                $skflg = TRUE;
        if (!$skflg)
            throw new ImageError("This image format unsupported");

        if ($picture_arr["size"] > 1048576)						// IMPORTANT: MAXfile declaration
            throw new ImageError("Image can't exceed 1 Mb in size");

        if (file_exists($target_filename)) {
            chmod($target_filename, 0755);
            unlink($target_filename);
        }

        if (!move_uploaded_file($picture_arr["tmp_name"], $target_filename))
            throw new ImageError("Unknown error occured during upload, retrying might help");

        return $dbname;

    }



    // TODO add proportions
    final public static function cropimage(string $address) {

        $w = getimagesize($address)[0];
        $h = getimagesize($address)[1];

        if ($w == $h)
            return;

        $img = WideImage::load($address);

        if ($w > $h)
            $cr = $img -> crop(floor(($w - $h)/2), 0, $h, $h);
        else $cr = $img -> crop(0, floor(($h - $w)/2), $w, $w);

        $cr -> saveToFile($address);

    }


    public static function registerWithImages(DataCollection $valuearr, DataCollection $images) {

        $id = self::populateId($valuearr);
        foreach ($images as $key => $image) {
            $valuearr[$key] = self::registerImg($image, 'models/'.md5($id));
        }
        return parent::add($valuearr);
    }

    // TODO add thumb-generating function

}


