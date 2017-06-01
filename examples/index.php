    <?php

require __DIR__ . '/../vendor/autoload.php';

    use \aotd\Captcha\AvitoPhone;

/* @var DirectoryIterator $file */
foreach (new DirectoryIterator(__DIR__ . '/test') as $file) {
    if ($file->getExtension() !== 'png') {
        continue;
       
        }

    $image = new Imagick($file->getPathname());
    $checkValue = $file->getBasename('.png');

    $preprocessed = AvitoPhone::preprocess($image);
    $recognized = AvitoPhone::recognition( $preprocessed, AvitoPhone::segmentation($preprocessed) );
    $recognized = strtr($recognized, array('-'=>''));
    if ($recognized !== $checkValue) {
        echo "Recognition error. Recognized value '$recognized' differs original value '$checkValue'\n";
    } else {
        echo "Recognition $recognized - ok!\n";
        
     }
}
