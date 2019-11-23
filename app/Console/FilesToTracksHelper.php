<?php

namespace App\Console;

use getID3                               as ID3;
use Stdclass                             as Std;
use Exception                            as Ex;
use getid3_lib                           as ID3Lib;
use App\Grep\Entity\Api                  as Api;
use Illuminate\Support\Collection        as Coll;
use Illuminate\Database\QueryException   as QEx;
use SnapyCloud\PhpApi\Client\SnapyClient as Client;

class FilesToTracksHelper
{
    /**
     * For remote request, set debug to false.
     *
     * @var boolean
     */
    private $debug;

    /**
     * Is true, If input path is directory.
     *
     * @var boolean
     */
    private $input_is_dir;

    /**
     * Is true, If input path is file.
     *
     * @var boolean
     */
    private $input_is_file;

    /**
     * Permission to duplicated file overwrite.
     *
     * @var boolean
     */
    private $file_overwrite;

    /**
     * Instance of ID3 object.
     *
     * @var ID3
     */
    private $grep;

    /**
     * Instance of SnapyClient object.
     *
     * @var Client
     */
    private $client;

    /**
     * Input File/Directory path.
     *
     * @var string
     */
    private $input_path;

    /**
     * Data for insert into database.
     *
     * @var array
     */
    private $insert_data;

    /**
     * Data for update from database.
     *
     * @var array
     */
    private $update_data;

    /**
     * Result of insert into database.
     *
     * @var array
     */
    private $insert_result;

    /**
     * Result of update from database.
     *
     * @var array
     */
    private $update_result;

    /**
     * List of source files.
     *
     * @var array
     */
    private $src_files;

    /**
     * List of failed tracks.
     *
     * @var array
     */
    private $fialed_track_to;

    /**
     * The directory of source files.
     *
     * @var string
     */
    private $src_dir;

    /**
     * Repository on remote server.
     *
     * @var string
     */
    private $remote_repo_path = "/home/app/music/repository/";

    /**
     * Repository on local.
     *
     * @var string
     */
    private $local_repo_path  = "/home/devops/Documents/";
    // private $local_repo_path  = "/Users/mirza/Documents/";

    /**
     * Create a new instance.
     *
     * @return void
     */
    public function __construct ()
    {
    	/* Set php config to show all errors. */
    	error_reporting( E_ALL | E_STRICT );
    }

    /**
     * Handle an incoming file/directory.
     *
     * @param  string  $path
     * @param  boolean  $file_overwrite
     * @param  boolean  $debug
     * @return void
     */
    public function handler ( $path, $file_overwrite = true, $debug = false )
    {
        $this->setDebug( $debug );  /* Do some initialization.       */
        $this->setInputPath( $path );
        $this->setOverwriteFlag( $file_overwrite );

        $this->inputPathSupplier(); /* Do input path validation.     */

        $this->prepareInsertData();
        $this->doInsert();          /* Do insert with prepared data. */

        $this->prepareUpdateData();
        $this->doUpdate();          /* Do update with prepared data. */

        /* The end of command with success message. */
        $this->terminate( 'Command successfully done!' );
    }

    /**
     * Check input path and validate it.
     *
     * @return void
     */
    private function inputPathSupplier ()
    {
        /*
         * Check the input path to pass the validation,
         * If failed, the command will stop with appropriate message.
         */
        $this->inputPathValidation();

        /* Set the source directory, contains directory path that file itself settles in. */
        $this->setSourceDirectory();

        /*
         * The input, wheter file or directory, as source file(s),
         * Will list into $raw_src_files, and if file(s) not found,
         * the command will terminates.
         */
        if ( ! $raw_src_files = $this->getRawSourceFiles() ) {
            $msg = 'File(s) not found :' . "\n" . $this->getInputPath();
            $this->terminate( $msg );
        }

        $src_files = [];
        foreach ( $raw_src_files as $raw_src_file ) {

            /*
             * Split file's name and file's extension of file,
             * And then adds to the $src_files property as an array.
             */
            $pathinfo = pathinfo( $raw_src_file );

            $filename  =       $pathinfo[ 'filename'  ];
            $extension = '.' . $pathinfo[ 'extension' ];

            $src_files[] = compact( 'filename', 'extension' );
        }

        /*
         * Remove duplicated files and get the unique files as resources.
         * If uniqueness cause to lack of source files, command will terminate.
         */
        if ( ! $unique_src_files = $this->getUniqueArray( $src_files ) ) {
            $msg = 'Files not found :' . "\n" . $this->getInputPath();
            $this->terminate( $msg );
        }

        $this->setSourceFiles( $unique_src_files ); /* Set $src_files property. */

        /*
         * Now that input validation passed,
         * It is time to initialize some property.
         */
        $this->init();
    }

    /**
     * Prepare data to insert.
     *
     * @return void
     */
    private function prepareInsertData ()
    {
        /*
         * To prepare insert data, Add artist name to each source file data,
         * and then add this complete data to insertable data array.
         */

        $insert_data = [];

         /* Get all files that thay are condidate to upload */
        foreach ( $src_files = $this->getSourceFiles() as $src_file ) {

            $name      = $src_file[ 'filename'  ];
            $extension = $src_file[ 'extension' ];

            $basename  = $name . $extension;

            /*
             * Fetch file name and artist name from file info.
             * The filename uses to ensure that, the data matches up to the file's info.
             */
            $keys = [ 'filename', 'artist' ];
            if ( ! $file_info = $this->getMetadata( $basename, $keys ) ) {

                $this->setFailedTrack(     /* Add track to failed tracks. */
                    'without file info',   /* Associated message.         */
                    $basename              /* file's basename.            */
                );

                /* Because of lack of file info, continue with next $data. */
                continue;
            }
            elseif (
                /* Check artist name existance. */
                ! (
                    isset( $file_info[ 'artist' ] ) &&
                    ! empty( $file_info[ 'artist' ] )
                )
            ) {
                $this->setFailedTrack(     /* Add track to failed tracks. */
                    'lack of artist',      /* Associated message.         */
                    $basename              /* file's basename.            */
                );

                /* Because of lack of 'artist' name, continue with next $data. */
                continue;
            }

             /* If exists, update 'name' value by fetched 'name' value from file info. */
            $name =
                isset( $file_info[ 'filename' ] ) && ! empty( $file_info[ 'filename' ] )
                    ? pathinfo( $file_info[ 'filename' ] )[ 'filename' ]
                    : $name;

            /* Set artist name by fetched 'artist' value from file's info. */
            $artist = $file_info[ 'artist' ];

            $name   = trim( $name   ); /* Trim witespace from name and artist. */
            $artist = trim( $artist ); /* Also trim this.

            /* Add array of name, artist and extension to the $insert_data array. */
            $insert_data[] = compact( 'name', 'artist', 'extension' );
        }

        $this->setInsertData( $insert_data ); /* Set $insert_data property. */
    }

    /**
     * Insert into database.
     *
     * @return void
     */
    private function doInsert ()
    {
        /*
         * Inserting data to tracks table with two column includes name and artist,
         * and get id per inserted record.
         */

        $results = [];

        /* Terminate command if there is nothing to insert. */
        if ( ! $insert_data = $this->getInsertData() )
            $this->terminate( 'Nothing to do inserting' );

        foreach ( $insert_data as $data ) {

            /*
             * If data not set, or is null, or is not array, or is empty array,
             * continue with the next $data.
             */
            $data_is_valid = isset( $data ) && is_array( $data ) && count( $data );
            if ( ! $data_is_valid ) continue;

            /* The necessary keys that each data must be includes. */
            $necessary_keys =
                [
                    'name',
                    'artist',
                    'extension',
                ];

            foreach ( $necessary_keys as $key ) {

                /*
                 * Declare variables dynamically,
                 * $name, $artist and $extension.
                 * Check declared vaiable to have the valid value,
                 * otherwise will set to false.
                 */
                $$key =
                    isset( $data[ $key ] ) && ! empty( $data[ $key ] )
                        ? $data[ $key ]
                        : false;

                /*
                 * Except 'extension', if necessary keys are equals to false,
                 * continue with next $data.
                 */
                if ( ! $$key ) {
                    if ( $key == 'extension' ) break;

                    $this->setFailedTrack(                       /* Add track to failed tracks. */
                        'problem in prepared data to insert',    /* Associated message.         */
                        $data[ 'name' ] . $data[ 'extension' ]   /* file's basename.            */
                    );

                    /* Because of incorrect insert data, continue with next $data. */
                    continue 2;
                }
            }

            /*
             * Select insert mode locally or remotely, based on $debug property,
             * and then save result of query, that is current inserted record.
             */
            $result =
                $this->isDebugMode()
                    ? $this->locallyInsert(  $name, $extension, $artist )
                    : $this->remotelyInsert( $name, $extension, $artist );

            if ( $result ) {

                /* Unset some unnecessary column of result. */
                unset(
                    $result[ 'created_at' ],
                    $result[ 'updated_at' ],
                    $result[ 'deleted_at' ]
                );

                if ( ! is_integer( $result[ 'id' ] ) )
                	$result[ 'id' ] = intval( $result[ 'id' ] );

                /* Add new key/value pair named 'extension' to result. */
                $result[ 'extension' ] = $extension;

                /* Save each insert's result as part of insert's results batch. */
                $results[] = $result;
            }
            else {

                $this->setFailedTrack(    /* Add track to failed tracks. */
                    'insert data',        /* Associated message.         */
                    $name . $extension    /* file's basename.            */
                );

                /* Because of incorrect insert data, continue with next $data. */
                continue;
            }
        }

        $this->setInsertResult( $results ); /* Set $insert_result property. */
    }

    /**
     * Prepare data to update.
     *
     * @return void
     */
    private function prepareUpdateData ()
    {
        /*
         * To prepare update data, get file's metadata,
         * and fetch its data to fill tracks table columns.
         */

        /* Terminate the command if insert query result is invalid. */
        if ( ! $insert_result = $this->getInsetResult() )
            $this->terminate( 'Insert result is empty' );

        $update_data = [];
        foreach ( $insert_result as $item ) {

            /*
             * A valid record's data to update, at list includes the basic data,
             * without these, the command can not proceed,
             * and continue with the next $item.
             */
            $basic_data = [ 'name', 'artist', 'extension' ];
            if ( ! $this->checkBasicDataExists( $item, $basic_data ) ) continue;

            /*
             * Declare variables dynamically,
             * $name, $artist and $extension.
             */
            foreach ( $basic_data as $key )
                $$key = $item[ $key ];

            $basename  = $name . $extension;

            /*
             * Check the file existance to proceed.
             * If not exists, continue with the next $item.
             */
            $file_path = $this->getSourceDirectory() . $basename;
            if ( ! $this->checkFileExistance( $file_path ) ) continue;

            /*
             * Also, to proceed, We need to check the basic file's metadata,
             * includes 'name', 'artist' and 'cover'.
             * If each of those data not exists, continue with the next $item.
             */
            $file_info = $this->checkBasicMetadata( $basename );
            if ( ! $file_info ) continue;

            /*
             * As finally checking, the inserted data,
             * and the file fetched info must be equal in the 'name' and 'artist' values.
             * This step is necessary to ensure that correct record will update.
             * If thay are unequal, continue with the next $item.
             */
            if ( ! $this->insertedAndInfoDataMatch(  $file_info, $item  ) )
                continue;

            /*
             * Fetch the cover from the file info that its key named 'data',
             * and then moving the cover as image file to the covers repository,
             * and finally set its path to the 'img' key of $item array.
             */
            $item[ 'img' ] = $this->moveCover( $item, $file_info[ 'data' ] );

            /*
             * Moving the source file from current directory to the files repository,
             * and then set its path to 'trackUrl' key of the $item array.
             */
            $item[ 'trackUrl' ] = $this->moveFile( $item );

            /* Prepare update data on the current $item. */
            $update_data[] = $this->completeUpdateData( $item );
        }

        $this->setUpdateData( $update_data ); /* Set $update_data property. */
    }

    /**
     * update data of database.
     *
     * @return void
     */
    private function doUpdate ()
    {
        /* Updating data of tracks table */

        $results = [];

        /* Terminate command if there is nothing to update. */
        if ( ! $update_data = $this->getUpdateData() )
            $this->terminate( 'Nothing to do updating' );

        foreach ( $update_data as $item ) {

            /*
             * If data not set, or is null, or is not array, or is empty array,
             * continue with the next $item.
             */
            $item_is_valid = isset( $item ) && is_array( $item ) && count( $item );
            if ( ! $item_is_valid ) continue;

            /* The unnecessary keys which must be unset from final $item array. */
            $unsetable_columns =
                [
                    'id',
                    'name',
                    'artist',
                    'extension',
                ];

            foreach ( $unsetable_columns as $column ) {

                /*
                 * Declare variables dynamically.
                 * $id, $name, $artist and $extension.
                 * Get these variable values from $item.
                 * And finally, unset these key from $item.
                 */
                $$column =
                    isset( $item[ $column ] ) && ! empty( $item[ $column ] )
                        ? $item[ $column ]
                        : false;

                unset( $item[ $column ] );
            }

            /* Select update mode locally or remotely, based on $debug property. */
            $result =
                $this->isDebugMode()
                    ? $this->locallyUpdate(  $id, $item, $name )
                    : $this->remotelyUpdate( $id, $item, $name );

            /*
             * If result is valid value, adds to the results patch,
             * otherwise, do nothing.
             */
            if ( ! $result )
                $results[] = $result;
        }

        $this->setUpdateResult( $results ); /* Set $update_result property. */
    }

    /**
     * Terminate this artisan command.
     *
     * @param  string  $message
     * @return void
     */
    private function terminate ( $message = null )
    {
        /*
         * Terminate artisan command
         * and show message with failed tracks.
         */

        $msg = 'Command died';

        if ( $message )
            $msg .= " with message which is '" . $message . "'.";

        /* Php's end of line built-in constant ( PHP_EOL ) Concate to the message */
        $msg .= PHP_EOL;

        /* Export failed tracks at the end of command */
        if ( $faild_tracks = $this->getFailedTrack() )
            foreach ( $faild_tracks as $key => $value )
                $msg .= ( 'fialed track to ' . $key . ' : ' . count( $value ) . PHP_EOL );

        /* To end command with appropriate message */
        die( $msg );
    }

    /**
     * Check item to contains basic data.
     *
     * @param  array  $item
     * @param  array  $basic_data
     * @return boolean
     */
    private function checkBasicDataExists ( $item, $basic_data )
    {
        /*
         * Check all basic data in $item,
         * if each data can not pass validation, the false will return.
         */
        foreach ( $basic_data as $key ) {

            /* Check if $item[ $key ] is set, not null and not empty. */
            $valid = isset( $item[ $key ] ) && ! empty( $item[ $key ] );

            /* If failed in the validation ... */
            if ( ! $valid ) {
                $this->setFailedTrack(                    /* Add track to failed tracks. */
                    "Missing fils's correct data " .
                    'include name, artist or extension ' .
                    'after insert data',                   /* Associated message.         */
                    ''                                     /* Missing file's basename.    */
                );

                /* Return false because of lack of file info. */
                return false;
            }
        }

        /* Return true because of all basic data passes the validation. */
        return true;
    }

    /**
     * Check the basic metadata in the fetched file's info.
     *
     * @param  string  $file_basename
     * @return array|boolean
     */
    private function checkBasicMetadata ( $file_basename )
    {
        /*
         * Check the basic metadata in the fetched file's info,
         * If the file info can not passes the validation,
         * false will return,
         * otherwise file info itself will return.
         */

        $metadata =
            [
                'data',
                'artist',
                'filename',
            ];

        /*
         * The getMetadata() method will return false,
         * If each the element of $metadata not found,
         * otherwise, the array of data will return.
         */
        if ( ! $file_info = $this->getMetadata( $file_basename, $metadata ) ) {

            /* If failed in the validation ... */
            $this->setFailedTrack(           /* Add track to failed tracks. */
                'Missing info after insert', /* Associated message.         */
                $file_basename               /* file's basename.            */
            );

            /* Return false because of lack of file info. */
            return false;
        }

        return $file_info;
    }

    /**
     * Check a file existance.
     *
     * @param  string  $file_path
     * @return boolean
     */
    private function checkFileExistance ( $file_path )
    {
        /*
         * Check the file existance,
         * which that the $file_path point to.
         */
        if ( ! is_file( $file_path ) ) {

            /* If file not exists ... */
            $this->setFailedTrack(                         /* Add track to failed tracks. */
                'lack of source file after insert data',   /* Associated message.         */
                pathinfo( $file_path )[ 'basename' ]       /* file's basename.            */
            );

            /* Return false because of lack of source file. */
            return false;
        }

        return true;
    }

    /**
     * Check the file info and basic data that to be belongs to the same file.
     *
     * @param  array  $file_info
     * @param  array  $basic_data
     * @return boolean
     */
    private function insertedAndInfoDataMatch ( $file_info, $basic_data )
    {
        /*
         * Check the file info and basic data that to be belongs to the same file.
         * Via equivalent validation in basic parameters includes 'name' and 'artist'.
         */

        /* Extract necessary variable/value from input parameter */
        $name      = $basic_data[ 'name'      ];
        $artist    = $basic_data[ 'artist'    ];
        $extension = $basic_data[ 'extension' ];

        $basename  = $name . $extension;

        foreach ( $file_info as $key => $value ) {

            $valid =
                  isset( $file_info[ $key ] ) &&                       /* key must be set and not null.    */
                ! empty( $file_info[ $key ] ) &&                       /* key must be not empty.           */
                ( $key === 'artist' ? $artist   === $value : true ) && /* 'Artist' and 'filename' element, */
                ( $key === 'name'   ? $basename === $value : true );   /* must be the same.                */

            /* If the validation not passed ... */
            if ( ! $valid ) {
                $this->setFailedTrack(                   /* Add track to failed tracks. */
                    'inequal info and inserted record',  /* Associated message.         */
                    $basename                            /* file's basename.            */
                );

                /* Return false because of unmatched data and info. */
                return false;
            }
        }

        return true;
    }

    /**
     * Initialize some property.
     *
     * @return void
     */
    private function init ()
    {
        $this->grep   = new ID3;                /* ID3 help us to fetch file's metadata. */
        $this->client = ( new Api )->client();  /* Client help us to remote access.      */
    }

    /**
     * Insert into local database.
     *
     * @param  string  $name
     * @param  string  $extension
     * @param  string  $artist
     * @return array|boolean
     */
    private function locallyInsert ( $name, $extension, $artist )
    {
        /*
         * Insert record on local database.
         * Target table is 'Tracks'.
         */

        $result = null;

        try {

            /*
             * With select query, beforehand insert query,
             * we check the database's tracks table to ensure that
             * it has record with the same data includes 'name' and 'artist' columns.
             * If answer is yes, we prevent to create the duplicated data on database with insert query.
             */

            /* Selecte query. */
            $result =
                \DB::table( 'tracks' )
                    ->where( [ [ 'name', $name ], [ 'artist', $artist ] ] )
                    ->first();

            if ( $result ) {

                /* Convert the query result wheter Collection or StdClass or ..., to the array. */
                $result = $this->convertToArray( $result );

                /* If the converted result is valid, return it, otherwise nothing to do. */
                if ( isset( $result ) && is_array( $result ) && count( $result ) )
                    return $result;
            }
        }

        /* The catch block of select query exception. */
        catch ( QEx $e ) {

            $this->setFailedTrack(                /* Add track to failed tracks. */
                'to exception in select query',   /* Associated message.         */
                $name . $extension                /* file's basename.            */
            );
        }

        try {

            /*
             * Insert query in order to create new record in tracks table,
             * with the values of 'name' and 'artist' columns.
             */
            $result =
                \DB::table( 'tracks' )
                    ->insert( compact( 'name', 'artist' ) );
        }

        /* The catch block of insert query exception. */
        catch ( QEx $e ) {

            $this->setFailedTrack(                /* Add track to failed tracks. */
                'to exception in insert query',   /* Associated message.         */
                $name . $extension                /* file's basename.            */
            );

            /* Return false if exception throwd. */
            return false;
        }

        if ( $result ) {

            try {

                /* Retrive recently inserted record. */
                $result =
                    \DB::table( 'tracks' )
                        ->where( [ [ 'name', $name ], [ 'artist', $artist ] ] )
                        ->first();

                if ( $result ) {

                    /* Convert the query result wheter Collection or StdClass or ..., to the array. */
                    $result = $this->convertToArray( $result );

                    /* If the converted result is valid, return it, else nothing to do. */
                    if ( isset( $result ) && is_array( $result ) && count( $result ) )
                        return $result;
                }

                /* Return false if correct result not exists. */
                return false; 
            }

            /* The catch block of select query exception. */
            catch ( QEx $e ) {

                $this->setFailedTrack(                  /* Add track to failed tracks. */
                    'to exception in select query ' .
                    'after insert query',               /* Associated message.         */
                    $name . $extension                  /* file's basename.            */
                );

                /* Return false in throwing query exception. */
                return false;
            }
        }

        /* Return false if correct result not exists. */
        return false;
    }

    /**
     * Insert into remote database.
     *
     * @param  string  $name
     * @param  string  $extension
     * @param  string  $artist
     * @return array|boolean
     */
    private function remotelyInsert ( $name, $extension, $artist )
    {
        /*
         * Insert record on remote database.
         * Target table is 'Tracks'.
         */

        $result = null;

        /*
         * The $getClient() method returns instance of SnapyClient Class,
         * that handle remote access/request to the api.
         */
        if ( ! $client = $this->getClient() ) {

            $this->setFailedTrack(             /* Add track to failed tracks. */
                'to establish connection ' .
                'to api as client',            /* Associated message.         */
                $name . $extension             /* file's basename.            */
            );

            /* Return false because of lack of remote api connection. */
            return false;
        }

        /*
         * Beforehand insert data, we check the database's tracks table,
         * to find record which it has the same 'name' and 'artist'.
         * If record found, insert request is canceled,
         * to prevent to have the duplicated record that point to the same file.
         */

        $result =
            $client->request(
                'GET', 'track',
                compact( 'name', 'artist' )
            );

        /* Check if api request result is valid and reliable. */
        if ( $this->validationResultOfApiRequest( $result ) ) {

            /* Convert the request result wheter Collection, StdClass or others, to the array. */
            $result = $this->convertToArray( $result );

            /* Validation of converted result of api request. */
            $converted_result_is_ok =
                isset( $result ) &&
                    is_array( $result ) &&
                        count( $result );

            /* If converted result is invalid ... */
            if ( ! $converted_result_is_ok ) {

                $this->setFailedTrack(                  /* Add track to failed tracks. */
                    'to convert result of request ' .
                    'to the api to array',              /* Associated message.         */
                    $name . $extension                  /* file's basename.            */
                );

                /* Because of bad converted result, return false. */
                return false;
            }

            return $result;
        }

        /* Create new record in tracks table, contains 'name' and 'artist'. */
        $result =
            $client->request(
                'POST', 'track',
                compact( 'name', 'artist' )
            );

        /* Check if api request result is valid and reliable. */
        if ( $this->validationResultOfApiRequest( $result ) ) {

            /* Convert the request result wheter Collection, StdClass or others, to the array. */
            $result = $this->convertToArray( $result );

            /* Validation of converted result of api request. */
            $converted_result_is_ok =
                isset( $result ) &&
                    is_array( $result ) &&
                        count( $result );

            /* If converted result is invalid ... */
            if ( ! $converted_result_is_ok ) {

                $this->setFailedTrack(                   /* Add track to failed tracks. */
                    'to convert result of request ' .
                    'to the api to array',               /* Associated message.         */
                    $name . $extension                   /* file's basename.            */
                );

                /* Because of bad converted result, return false. */
                return false;
            }

            return $result;
        }

        return false;
    }

    /**
     * Validation returned result of api request.
     *
     * @param  mixed  $request_result
     * @return boolean
     */
    private function validationResultOfApiRequest ( $request_result )
    {
    	// TODO: Check api result to be valid.
    }

    /**
     * Update local database.
     *
     * @param  integer  $id
     * @param  array  $data
     * @return array|boolean
     */
    private function locallyUpdate ( $id, $data, $file_name )
    {
        /*
         * Update record with $data on local database.
         * Target table is 'Tracks'.
         */

        $result = null;

        try {

            /* Update query. */
            $result =
                \DB::table( 'tracks' )
                    ->where( 'id', $id )
                    ->update( $data );
        }

        /* The catch block of update query exception. */
        catch ( QEx $e ) {
            $this->setFailedTrack(             /* Add track to failed tracks. */
                'via update query excption',   /* Associated message.         */
                $file_name                     /* file's basename.            */
            );

            /* Return false if exception throwd. */
            return false;
        }

        /* The result of update query will be 1 just in successfull updating. */
        if ( $result === 1 ) {
            try {

                /* Retrive recently updated record. */
                $result =
                    \DB::table( 'tracks' )
                        ->where( 'id', $id )
                        ->first();

                /* Convert query result wheter Collection or StdClass or ..., to the array. */
                $result = $this->convertToArray( $result );

                /* If the converted result is valid, return it, otherwise return false to the caller. */
                return
                    isset( $result ) && is_array( $result ) && count( $result )
                        ? $result
                        : false;
            }

            /* The catch block of select query exception. */
            catch ( QEx $e ) {

                $this->setFailedTrack(             /* Add track to failed tracks. */
                    'via select updated result',   /* Associated message.         */
                    $file_name                     /* file's basename.            */
                );

                /* Return false if exception throwd. */
                return false;
            }
        }

        /* Return false if correct result not exists. */
        return false;
    }

    /**
     * Update remote database.
     *
     * @param  integer  $id
     * @param  array  $data
     * @return mixed
     */
    private function remotelyUpdate ( $id, $data, $file_name )
    {
    	/*
         * Update record with $data on remote database.
         * Target table is 'Tracks'.
         */

        $result = null;

        /* Update requst. */
        $result =
            $client->request(
                'PUT', 'track/' . $id, $data
            );

        /* Check the result of update request. */
        if ( ! $this->validationResultOfApiRequest( $result ) ) {

        	$this->setFailedTrack(             /* Add track to failed tracks. */
                'updated record ' .
                'via remote update request',   /* Associated message.         */
                $file_name                     /* file's basename.            */
            );

            /* Return false if returned result of remote request is not valid. */
            return false;
        }

		/* Retrive recently updated record. */
		$result =
            $client->request(
                'GET', 'track',
                compact( 'id' )
            );

        /* Check the result of select request. */
        if ( ! $this->validationResultOfApiRequest( $result ) ) {

        	$this->setFailedTrack(                     /* Add track to failed tracks. */
                'retrive recently updated record ' .
                'via remote select request',           /* Associated message.         */
                $file_name                             /* file's basename.            */
            );

            /* Return false if returned result of remote request is not valid. */
            return false;
        }

        /* Convert query result wheter Collection or StdClass or ..., to the array. */
        $result = $this->convertToArray( $result );

        /* If the converted result is valid, return it, otherwise return false to the caller. */
        return
            isset( $result ) && is_array( $result ) && count( $result )
                ? $result
                : false;
    }

    /**
     * Prepare data to update.
     *
     * @param  array  $data
     * @return void
     */
    private function completeUpdateData ( $data )
    {
        /* Extrac $id and $basename variable from $data. */
        $id       = $data[ 'id' ];
        $basename = $data[ 'name' ] . '.mp3';

        /* Exception of columns that no need to update. */
        $excepted_columns = [ 'id', 'name', 'extension' ];

        $assPath     = $this->getAccStreamRepoPath();   /* The default path of repository of ACC stream.   */
        $segmentPath = $this->getSegmentListRepoPath(); /* The default path of repository of segment list. */

        $stream      = $assPath     . $id . '.aac';        /* ACC stream file destination path.   */
        $segmentlist = $segmentPath . $id . '/track.m3u8'; /* Segment list file destination path. */

        /* Get duration of file from metadata. */
        $info = $this->getMetadata( $basename, [ 'playtime_string as duration' ] );
        $duration = $info[ 'duration' ];

        /* Some default and calculated columns. */
        $default =
            [
                'assignedUserId'   => 1,
                'assignedUserName' => 'root',
                'stream'           => $stream,
                'duration'         => $duration,
                'segmentlist'      => $segmentlist,
            ];

        /* Fill some columns that is without value in $data. */
        foreach ( $data as $column_name => $column_value ) {

            /*
             * Continue with the next column of $data,
             * if current column included in exceptions.
             */
            if ( in_array( $column_name, $excepted_columns ) )
                continue;

            /*
             * If current column is part of default values,
             * so fetch its value from $default array.
             * otherwise fetch its value from file's metadata.
             */
            if ( ! $new_value = $this->findValueByKeyInArray( $default, $column_name ) )
                $new_value = $this->getMetadata( $basename, [ $column_name ] )[ $column_name ];

            /* New value must be set, not null, not empty and not equal to 'null'. */
            $valid_new_value =
                isset( $new_value )   &&
                    ! empty( $new_value ) &&
                        $new_value != 'null';

            /*
             * The column value will update if and only if
             * new value have been valid and not equal to old value.
             * in other word, have new data.
             */
            $data[ $column_name ] =
                ( $valid_new_value && ( $new_value != $column_value ) )
                    ? $new_value
                    : $column_value;
        }

        /* Return updated $data. */
        return $data;
    }

    /**
     * Create and Move file's cover to repository.
     *
     * @param  array  $item_data
     * @param  string  $binary_cover
     * @return string
     */
    private function moveCover ( $item_data, $binary_cover )
    {
        /*
         * Move source file to project repository.
         */

        /*
         * Declare basic data variables dynamically.
         * $name, $artist and $extension.
         */
        foreach ( [ 'id', 'name', 'extension' ] as $variable )
        	$$variable =
        		isset( $item_data[ $variable ] ) && ! empty( $item_data[ $variable ] )
        			? $item_data[ $variable ]
        			: false;

        $file_basename = $name . $extension;

        /* Check cover parameter to be not empty. */
        if ( empty( $binary_cover ) ) {

            $this->setFailedTrack(        /* Add track to failed tracks. */
                'lack of binary cover',   /* Associated message.         */
                $file_basename            /* file's basename.            */
            );

            /* Because of lack of cover data, return empty string. */
            return '';
        }

        /* Make destination directory if not exists. */
        if ( ! file_exists( $this->getCoversRepoPath() ) )
            mkdir( $this->getCoversRepoPath() );

        /* Final cover file destination concatenated with its new name. */
        $dest = $this->getCoversRepoPath() . $id . '.jpg';

        /*
         * In situation that user set file overwrite flag to false,
         * that means we have not permission to overwrite duplicated files,
         * if file with the same name exists in destination directory,
         * file moving will stop and empty string will return.
         */
        if ( file_exists( $dest ) && ! $this->getOverwriteFlag() ) {

            $this->setFailedTrack(            /* Add track to failed tracks. */
                'move cover because file ' .
                'with same name exist',       /* Associated message.         */
                $file_basename                /* file's basename.            */
            );

            /* Return empty string in file overwrite problem. */
            return '';
        }

        try {

            /*
             * TODO: Handle file permission problem for unlink().
             *
             * In situation that user set file overwrite flag to true,
             * that means we have permission to overwrite duplicated files,
             * if file with the same name exists in destination directory,
             * we unlink that file ( remove ) and the file moving proceed.
             */
            if ( file_exists( $dest ) )
                unlink( $dest );

            /* Create image file from cover data. */
            if ( $cover = @imagecreatefromstring( $binary_cover ) ) {

                /*
                 * Create jpeg file in destination from image file,
                 * with 100 percent quality.
                 */
                imagejpeg( $cover, $dest, 100 );
                imagedestroy( $cover ); /* Destroy allocated memory to create image. */
                return $dest; /* Return cover path. */
            }

            $this->setFailedTrack(         /* Add track to failed tracks. */
                'create and move cover',   /* Associated message.         */
                $file_basename             /* file's basename.            */
            );

            /* Return empty string because of problem in the cover moving. */
            return '';
        }
        catch ( Exception $e ) {
            $this->setFailedTrack(         /* Add track to failed tracks. */
                'create and move cover',   /* Associated message.         */
                $file_basename             /* file's basename.            */
            );

            /* Return empty string in throwing exception from jpeg file building. */
            return '';
        }

        /* Return empty string if cover moving encounter with problem. */
        return '';
    }

    /**
     * Move file to repository.
     *
     * @param  array  $item_data
     * @return string
     */
    private function moveFile ( $item_data )
    {
        /*
         * Move source file to project repository
         */

        /* Check if repository path of destination exists. */
        if ( ! $track_repo_path = $this->getTracksRepoPath() )
        	return '';

		/*
         * Declare basic data variables dynamically.
         * $name, $artist and $extension.
         */
        foreach ( [ 'id', 'name', 'extension' ] as $variable )
        	$$variable =
        		isset( $item_data[ $variable ] ) && ! empty( $item_data[ $variable ] )
        			? $item_data[ $variable ]
        			: false;

        $file_basename = $name . $extension;

        /* Make destination directory if not exists */
        if ( ! file_exists( $track_repo_path ) )
            mkdir( $track_repo_path );

        /* Final file destination concatenated with its new name */
        $dest = $track_repo_path . $id . $extension;

        /* Source file origin concatenated with its current name */
        $src  = $this->getSourceDirectory() . $file_basename;

        /*
         * In situation that user set file overwrite flag to false,
         * that means we have not permission to overwrite duplicated files,
         * if file with the same name exists in destination directory,
         * file moving will stop.
         */
        if ( file_exists( $dest ) && ! $this->getOverwriteFlag() ) {

            $this->setFailedTrack(            /* Add track to failed tracks. */
                'move file because file ' .
                'with same name exist',       /* Associated message.         */
                $file_basename                /* file's basename.            */
            );

            /* Return empty string in file overwrite problem */
            return '';
        }

        /*
         * TODO: Handle file permission problem for unlink().
         *
         * In situation that user set file overwrite flag to true,
         * that means we have permission to overwrite duplicated files,
         * if file with the same name exists in destination directory,
         * we unlink that file ( remove ) and the file moving proceed.
         */
        if ( file_exists( $dest ) )
            unlink( $dest );

        /*
         * Copy origin file to destination,
         * that cause to rename file automatically.
         */
        if ( @copy( $src, $dest ) )
            return $dest;

        /*
         * If any problem occurred which that prevent to return $dest,
         * the track adds to the failed tracks,
         * and empty string will return.
         */
        $this->setFailedTrack(   /* Add track to failed tracks. */
            'move file',         /* Associated message.         */
            $file_basename       /* file's basename.            */
        );

        /* Return empty string if file moving encounter with problem. */
        return '';
    }

    /*
     * Setters
     */

    /**
     * Set $input_path property.
     *
     * @param  string  $path
     * @return void
     */
    private function setInputPath ( $path )
    {
        $this->input_path = $path;
    }

    /**
     * Set $src_files property.
     *
     * @param  array  $src_files
     * @return void
     */
    private function setSourceFiles ( $src_files )
    {
        return $this->src_files = $src_files;
    }

    /**
     * Set $update_data property.
     *
     * @param  array  $update_data
     * @return void
     */
    private function setUpdateData ( $update_data )
    {
        return $this->update_data = $update_data;
    }

    /**
     * Set $update_result property.
     *
     * @param  array  $update_result
     * @return void
     */
    private function setUpdateResult ( $update_result )
    {
        return $this->update_result = $update_result;
    }

    /**
     * Set $file_overwrite property.
     *
     * @param  boolean  $permission
     * @return void
     */
    private function setOverwriteFlag ( $permission )
    {
        $this->file_overwrite = $permission;
    }

    /**
     * Set $debug property.
     *
     * @param  boolean  $debug
     * @return void
     */
    private function setDebug ( $debug )
    {
        $this->debug = $debug;
    }

    /**
     * Set $src_dir property.
     *
     * @return void
     */
    private function setSourceDirectory ()
    {
        /*
         * Set source files directory as class property,
         * wheter input path have been file or directory.
         */

        /* Terminate command if input path is incorrect. */
        if ( ! $input_path = $this->getInputPath() ) {
            $msg = 'Missing input path :' . "\n" . $input_path;
            $this->terminate( $msg );
        }

        $pathinfo = pathinfo( $input_path );

        /*
         * If input path is file,
         * set $input_is_file class property to true,
         * then assign its directory path to the $src_dir class property,
         * and finally, return to the caller function.
         */
        if ( is_file( $input_path ) ) {

            $this->input_is_file = true;
            $this->src_dir = $pathinfo[ 'dirname' ] . '/';

            return;
        }

        /*
         * If input path is directory,
         * set $input_is_dir class property to true,
         * then assign its directory path to the $src_dir class property,
         * and finally, return to the caller function.
         */
        elseif ( is_dir( $input_path ) ) {

            $this->input_is_dir = true;
            $this->src_dir = $pathinfo[ 'dirname' ] . '/' . $pathinfo[ 'basename' ] . '/';

            return;
        }

        /* Otherwise, terminate command because of invalid input path. */
        else {
            $msg = 'No such file or directory :' . "\n" . $input_path;
            $this->terminate( $msg );
        }
    }

    /**
     * Set $fialed_track_to property.
     *
     * @param  string  $message
     * @param  string  $file_basename
     * @return void
     */
    private function setFailedTrack ( $message, $file_basename )
    {
        $this->fialed_track_to[ $message ][] =
            $this->getSourceDirectory() . $file_basename;
    }

    /**
     * Set $insert_data property.
     *
     * @param  array  $insert_data
     * @return void
     */
    private function setInsertData ( $insert_data )
    {
        $this->insert_data = $insert_data;
    }

    /**
     * Set $insert_result property.
     *
     * @param  array  $insert_result
     * @return void
     */
    private function setInsertResult ( $insert_result )
    {
        $this->insert_result = $insert_result;
    }

    /*
     * Getters
     */

    /**
     * Get $grep property.
     *
     * @return ID3|boolean
     */
    private function getGrep ()
    {
        /*
         * The $grep property must be set and not null.
         * The $grep property must be instance of ID3 class.
         * otherwise, return false.
        */

        return
            isset( $this->grep ) && ( $this->grep instanceof ID3 )
                ? $this->grep
                : false;
    }

    /**
     * Get $client property.
     *
     * @return boolean|Client
     */
    private function getClient ()
    {
        /*
         * The $client property must be set and not null.
         * The $client property must be instance of ID3 class.
         * otherwise, return false.
        */

        return
            isset( $this->client ) && ( $this->client instanceof Client )
                ? $this->client
                : false;
    }

    /**
     * Get repository path.
     *
     * @return string|boolean
     */
    private function getRepoPath ()
    {
        /*
         * Repository, is a directory that specified to settle covers and files,
         * on the both of local machine and remote server.
         * Via debug propery, we decide to use local or remote repository.
         */

        $debug  = isset( $this->debug            ) ? $this->debug            : false;
        $local  = isset( $this->local_repo_path  ) ? $this->local_repo_path  : false;
        $remote = isset( $this->remote_repo_path ) ? $this->remote_repo_path : false;

        return $debug ? $local : $remote;
    }

    /**
     * Get $input_path property.
     *
     * @return string|boolean
     */
    private function getInputPath ()
    {
        /*
         * The $input_path property must be set and not null.
         * The $input_path property must be string.
         * The $input_path property must be not empty string.
         * The $input_path property must be not null string.
         * otherwise, return false.
        */

        return
            isset( $this->input_path )     &&
            is_string( $this->input_path ) &&
            ! empty( $this->input_path )   &&
            $this->input_path != 'null'
                ? $this->input_path
                : false;
    }

    /**
     * Get $src_files property.
     *
     * @return array|boolean
     */
    private function getSourceFiles ()
    {
        /*
         * The $src_files property must be set and not null.
         * The $src_files property must be array.
         * The $src_files property must be not empty array.
         * otherwise, return false.
        */

        return
            isset( $this->src_files )    &&
            is_array( $this->src_files ) &&
            count( $this->src_files )
                ? $this->src_files
                : false;
    }

    /**
     * Get $insert_data property.
     *
     * @return array|boolean
     */
    private function getInsertData ()
    {
        /*
         * The $insert_data property must be set and not null.
         * The $insert_data property must be array.
         * The $insert_data property must be not empty array.
         * otherwise, return false.
        */

        return
            isset( $this->insert_data )    &&
            is_array( $this->insert_data ) &&
            count( $this->insert_data )
                ? $this->insert_data
                : false;
    }

    /**
     * Get $fialed_track_to property.
     *
     * @return array|boolean
     */
    private function getFailedTrack ()
    {
        /*
         * The $fialed_track_to property must be set and not null.
         * The $fialed_track_to property must be array.
         * The $fialed_track_to property must be not empty array.
         * otherwise, return false.
        */

        return
            isset( $this->fialed_track_to )    &&
            is_array( $this->fialed_track_to ) &&
            count( $this->fialed_track_to )
                ? $this->fialed_track_to
                : false;
    }

    /**
     * Get $src_dir property.
     *
     * @return string|boolean
     */
    private function getSourceDirectory ()
    {
        /*
         * The $src_dir property must be set and not null.
         * The $src_dir property must be string.
         * The $src_dir property must be not empty string.
         * The $src_dir property must be not null string.
         * otherwise, return false.
        */

        return
            isset( $this->src_dir )     &&
            is_string( $this->src_dir ) &&
            ! empty( $this->src_dir )   &&
            $this->src_dir != 'null'
                ? $this->src_dir
                : false;
    }

    /**
     * Get $update_data property.
     *
     * @return array|boolean
     */
    private function getUpdateData ()
    {
        /*
         * The $update_data property must be set and not null.
         * The $update_data property must be array.
         * The $update_data property must be not empty array.
         * otherwise, return false.
        */

        return
            isset( $this->update_data )    &&
            is_array( $this->update_data ) &&
            count( $this->update_data )
            ? $this->update_data
            : false;
    }

    /**
     * Get $file_overwrite property.
     *
     * @return boolean
     */
    private function getOverwriteFlag ()
    {
        /*
         * The $file_overwrite property must be set and not null.
         * The $file_overwrite property must be boolean.
         * otherwise, return true.
        */

        return
            isset( $this->file_overwrite ) && is_bool( $this->file_overwrite )
                ? $this->file_overwrite
                : true;
    }

    /**
     * Get $insert_result property.
     *
     * @return array|boolean
     */
    private function getInsetResult ()
    {
        /*
         * The $insert_result property must be set and not null.
         * The $insert_result property must be array.
         * The $insert_result property must be not empty array.
         * otherwise, return false.
        */

        return
            isset( $this->insert_result )    &&
            is_array( $this->insert_result ) &&
            count( $this->insert_result )
                ? $this->insert_result
                : false;
    }

    /**
     * Get $update_result property.
     *
     * @return array|boolean
     */
    private function getUpdateResult ()
    {
        /*
         * The $update_result property must be set and not null.
         * The $update_result property must be array.
         * The $update_result property must be not empty array.
         * otherwise, return false.
        */

        return
            isset( $this->update_result )    &&
            is_array( $this->update_result ) &&
            count( $this->update_result )
                ? $this->update_result
                : false;
    }

    /**
     * Get repository path of tracks.
     *
     * @return string|boolean
     */
    private function getTracksRepoPath ()
    {
        /*
         * If getRepoPath() method not returns false,
         * return its result concatenated with 'track/',
         * else return false.
         */

        return
            $this->getRepoPath()
                ? $this->getRepoPath() . "track/"
                : false;
    }

    /**
     * Get repository path of covers.
     *
     * @return string|boolean
     */
    private function getCoversRepoPath ()
    {
        /*
         * If getRepoPath() method not returns false,
         * return its result concatenated with 'cover/',
         * else return false.
         */

        return
            $this->getRepoPath()
                ? $this->getRepoPath() . "cover/"
                : false;
    }

    /**
     * Get repository path of ACC stream.
     *
     * @return string|boolean
     */
    private function getAccStreamRepoPath ()
    {
        /*
         * If getTracksRepoPath() method not returns false,
         * return its result concatenated with 'stream/',
         * else return false.
         */

        return
            $this->getTracksRepoPath()
                ? $this->getTracksRepoPath() . "stream/"
                : false;
    }

    /**
     * Get repository path of segment list.
     *
     * @return string|boolean
     */
    private function getSegmentListRepoPath ()
    {
        /*
         * If getTracksRepoPath() method not returns false,
         * return its result concatenated with 'segment/',
         * else return false.
         */

        return 
            $this->getTracksRepoPath()
                ? $this->getTracksRepoPath() . "segment/"
                : false;
    }

    /**
     * Get extention of input file.
     *
     * @return string|boolean
     */
    private function getInputExtension ()
    {
        /*
         * If getInputPath() method not returns false,
         * return fetched extension form its result,
         * concatenated with dot ( . ) in front of it,
         * else return false.
         */

        return
            $this->getInputPath()
                ? '.' . pathinfo( $this->getInputPath() )[ 'extension' ]
                : false;
    }

    /**
     * Get filename of input file.
     *
     * @return string|boolean
     */
    private function getInputFileName ()
    {
        /*
         * If getInputPath() method not returns false,
         * return fetched filename form its result,
         * else return false.
         */

        return
            $this->getInputPath()
                ? pathinfo( $this->getInputPath() )[ 'filename' ]
                : false;
    }

    /**
     * Get basename of input file.
     *
     * @return string|boolean
     */
    private function getInputBaseName ()
    {
        /*
         * If getInputPath() method not returns false,
         * return fetched basename form its result,
         * else return false.
         */

        return
            $this->getInputPath()
                ? pathinfo( $this->getInputPath() )[ 'basename' ]
                : false;
    }

    /**
     * Get source file(s).
     *
     * @return array|boolean
     */
    private function getRawSourceFiles ()
    {
        /*
         * return files that input path point to, as array.
         */

        /*
         * If command called with just one file as input,
         * get basename of input ( name + extension ) by getInputBaseName() method,
         * and then return as array contains it.
         */
        if ( $this->inputIsFile() )
            return [ $this->getInputBaseName() ];

        /*
         * If command called with directory path as input,
         * we get list of all files in this directory,
         * and then apply filter on list which throughout that
         * files that end up with '.ext' will return.
         */
        elseif ( $this->inputIsDir() ) {

            $scan_result = scandir( $this->getSourceDirectory() );

            $pattern = '/^([^.])/';
            return preg_grep( $pattern, $scan_result );
        }

        /* The false returned if input path is neither a file nor a directory */
        else return false;
    }

    /**
     * Get file's metadata.
     *
     * @param  string  $basename
     * @param  array  $keys
     * @return array|boolean
     */
    private function getMetadata ( $basename, $keys )
    {
        /*
         * The function, get to arguments, file name and array of keys
         * that must be fetchd from file's metadata.
         * Also, we can to pass keys array's elements with 'as' word,
         * to specify new name for key that returned with its found value,
         * by the way [ 'playtime_string as duration' ]
         */

        $keys_is_ok     = $keys     &&   count( $keys     ); /* Input keys array validation */
        $basename_is_ok = $basename && ! empty( $basename ); /* Input file name validation  */

        $result = [];

        /* If parameters are valid, get file's metadata by getID3 */
        if ( $basename_is_ok && $keys_is_ok ) {
            $file_info = $this->getID3Info( $basename );

            foreach ( $keys as $key ) {

                /*
                 * Handle keys that must be returned with new name,
                 * via extract that new name from passed string as keys to the function.
                 */
                $as = $key;
                if ( strstr( $key, ' as ' ) ) {
                    $as = trim( str_replace( 'as', '', trim( strstr( $key, 'as' ) ) ) );
                    $key = trim( strstr( $key, 'as', true ) );
                }

                /*
                 * After key validation, find value by search in file's info array,
                 * and then create key/value pair in the result array.
                 */
                if ( isset( $key ) && is_string( $key ) && ! empty( $key ) ) {
                    if ( $value = $this->findValueByKeyFromFileInfo( $file_info, $key ) )
                        $result[ $as ] = $value;
                }
            }
        }

        /* At the end, If $result array contains elements, return it, otherwise return false */
        return count( $result ) ? $result : false;
    }

    /**
     * Get file's ID3 metadata.
     *
     * @param  string  $filename
     * @return array|boolean
     */
    private function getID3Info ( $filename )
    {
        /*
         * Fetch file's metadata.
         */

        /* Get instance of 'getID3' class. */
        $grep = $this->getGrep();

        /* We need to full path of file to fetch its metadata with getID3. */
        $file_full_path = $this->getSourceDirectory() . $filename;

        if ( $file_full_path && $grep ) {

            /* The analyze() method returns nested array of fetched metadata from the file */
            if ( $file_info = $grep->analyze( $file_full_path ) ) {

                /* The CopyTagsToComments() method sort some data in metadata array */
                ID3Lib::CopyTagsToComments( $file_info );

                return $file_info;
            }

            /*
             * Return false if the analyze() method return null,
             * or other false equivalent values.
             */
            return false;
        }

        /*
         * Return false if the file full path
         * or the getID3 object does not supplied
         */
        return false;
    }

    /**
    * Helpers
    */

    /**
     * Find value by key in array.
     *
     * @param  array  $haystack
     * @param  string  $needle
     * @return mixed
     */
    private function findValueByKeyInArray ( $haystack, $needle )
    {
        /*
         * Walk through an array named $haystack recursively,
         * to find specific key named $needle and then return its value.
         */

        $result = false;
        array_walk_recursive(
            $haystack,
            function ( $value, $key ) use ( $needle, &$result ) {
                if ( $key === $needle ) {
                    $result = $value;
                    return;
                }
            }
        );

        return
            ! is_null( $result ) && ! empty( $result )
            	? $result
            	: false;
    } 

    /**
     * Find specific value by key in file's metadata.
     *
     * @param  array  $file_info
     * @param  string  $key
     * @return string
     */
    private function findValueByKeyFromFileInfo ( $file_info, $key )
    {
        /*
         * Search in metadata array of file,
         * to find the specific key's value.
         */

        /* Find value of specific key in metadata array by other helper method. */
        $found_value = $this->findValueByKeyInArray( $file_info, $key );

        /* Apply some validation on found value */
        $found_value_is_ok =
            isset( $found_value )     &&  /* Found value must be set and not null */
            is_string( $found_value ) &&  /* Found value must be string           */
            ! empty( $found_value )   &&  /* Found value must be not empty string */
            $found_value != 'null';       /* Found value must be not null string  */

        return $found_value_is_ok ? $found_value : false;
    }

    /**
     * Return $input_is_file property.
     *
     * @return boolean
     */
    private function inputIsFile ()
    {
        /*
         * The $input_is_file property must be set and not null.
         * The $input_is_file property must be boolean.
         * otherwise, return true.
        */

        return
            isset( $this->input_is_file ) &&
            is_bool( $this->input_is_file )
                ? $this->input_is_file
                : false;
    }

    /**
     * Return $input_is_dir property.
     *
     * @return boolean
     */
    private function inputIsDir ()
    {
        /*
         * The $input_is_dir property must be set and not null.
         * The $input_is_dir property must be boolean.
         * otherwise, return true.
        */

        return
            isset( $this->input_is_dir ) &&
            is_bool( $this->input_is_dir )
                ? $this->input_is_dir
                : false;
    }

    /**
     * Return $debug property.
     *
     * @return boolean
     */
    private function isDebugMode ()
    {
        /*
         * The $debug property must be set and not null.
         * The $debug property must be boolean.
         * otherwise, return true.
        */

        return
            isset( $this->debug ) &&
            is_bool( $this->debug )
                ? $this->debug
                : false;
    }

    /**
     * Input validation.
     *
     * @return void
     */
    private function inputPathValidation ()
    {
        /*
         * If the input path can not passes the validation,
         * command will terminate with appropriate message.
         */
        if ( ! $input_path = $this->getInputPath() ) {
            $msg = 'Invalid path :' . "\n" . $this->getInputPath();
            $this->terminate( $msg );
        }
    }

    /**
     * Convert some datatype to array.
     *
     * @param  mixed  $input
     * @return array|boolean
     */
    private function convertToArray ( $input )
    {
        /*
         * Convert query result to array,
         * wheter input is Collection type or Stdclass.
         */

        /* Return first element of Collection as array. */
        if ( $input instanceof Coll )
            return
                $input->map(
                    function( $item )
                    {
                        return ( array ) $item;
                    }
                )->toArray()[ 0 ];

        /* Return Stdclass object as array. */
        elseif ( $input instanceof Std )
            return ( array ) $input;

        /* Return input itself if its data type is array. */
        elseif ( is_array( $input ) )
            return $input;

        /* Return false if input data type is other than above types. */
        else return false;

    }

    /**
     * Make array unique.
     *
     * @param  array  $array
     * @return array|boolean
     */
    private function getUniqueArray ( $array )
    {
        /*
         * For reliable array uniqueness,
         * first of all, we put on serialization on the each of element of array by array_map(),
         * after that, to unique the array's elements,
         * and finally, to revert to unserialized array, by using array_map() again.
         */

        $unique =
            array_map(
                'unserialize',
                array_unique(
                    array_map(
                        'serialize',
                        $array
                    )
                )
            );

        $validation =
            isset( $unique ) &&
                is_array( $unique ) &&
                    count( $unique );

        return $validation ? $unique : false;
    }
}