<!DOCTYPE html PUBLIC "-//W3C//DTC XHTML 1.0 Strict//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
    <title><?php echo $output_title ?></title>
</head>
<body>
    <h1><?php echo $output_title ?></h1>
    <p><?php echo $output_message ?></p>
    <table>
        <thread>
            <tr><th>File</th><th>Size</th></tr>
        </thread>
        <tbody>
        <?php foreach($fileList as $file): ?>
            <tr>
                <td>
                    <?php if($file['thumb'] != ''): ?>
                        <a href="<?php echo $file['url'] ?>">
                            <img src="<?php echo $file['thumb'] ?>"/>
                        </a>
                    <?php endif ?>
                </td>
                    <?php echo $file['size'] ?>
                </td>
            </tr>
        <?php endforeach ?>
        </tbody>
    </table>
</body>
</html>
