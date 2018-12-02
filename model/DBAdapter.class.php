<?php
/**
 * description
 *
 * Filename: DBAdapter.class.php
 *
 * @author liyan
 * @since 2015 2 4
 */
interface DBAdapter {
    public function connect();
    public function close();
    public function query($sql);
}