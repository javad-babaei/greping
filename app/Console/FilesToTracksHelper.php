<?php

namespace App\Console;

use getID3                        as ID3;
use Stdclass                      as Std;
use Exception                     as Ex;
use getid3_lib                    as ID3Lib;
use App\Grep\Entity\Api           as Api;
use Illuminate\Support\Collection as Coll;

class FilesToTracksHelper
{
    // For remote request, set debug to false
    protected $debug        = true;
    protected $no_overwrite = false;

    protected $api    = null;
    protected $grep   = null;
    protected $client = null;

    protected $path          = null;
    protected $insert_data   = null;
    protected $update_data   = null;
    protected $insert_result = null;
    protected $update_result = null;

    protected $src_files       = [];
    protected $fialed_track_to = [];

    // protected $cover_dir = "/home/app/music/repository/cover/";
    // protected $track_dir = "/home/app/music/repository/track/";
    protected $cover_dir = "/home/devops/Documents/";
    protected $track_dir = "/home/devops/Documents/";

    protected $src_dirname = '';

    public function handler( $path, $no_overwrite = false )
    {
        $this->path         = $path;
        $this->no_overwrite = $no_overwrite;

        $this->optionSupplier();
        $this->prepareInsertData();
        $this->doInsert();

        $insert_result =
            isset( $this->insert_result )
            ? $this->insert_result
            : [];

        foreach ( $insert_result as $item ) {

            $item_src_dir =
                $this->src_dirname
                . $item[ 'name'      ]
                . $item[ 'extension' ];

            if ( is_file( $item_src_dir ) ) {

                /* Get src_file metadata */
                $item[ 'info' ] =
                    $this->getMetadata(
                        $item[ 'name'      ],
                        $item[ 'extension' ]
                    );

                $item_info_name =
                    isset( $item[ 'info' ][ 'filename' ] )
                    ? $item[ 'info' ][ 'filename' ]
                    : null;

                $item_info_artist = $this->hasArtist( $item[ 'info' ] ) ?? null;

                if (
                    $item[ 'artist' ] == $item_info_artist &&
                    $item[ 'name' ] . $item[ 'extension' ] == $item_info_name
                ) {
                    $item_binary_cover =
                        $this->hasBinaryCover( $item[ 'info' ] ) ?? null;

                    if (
                                 ( $item_binary_cover ) &&
                          ! empty( $item_binary_cover ) &&
                        ! is_null( $item_binary_cover )
                    )
                        $item[ 'img' ] =
                            $this->moveCover(
                                $item[ 'id'        ],
                                $item[ 'name'      ],
                                $item[ 'extension' ],
                                $item_binary_cover
                            );
                    else
                        $this->fialed_track_to[ 'lack of binary cover' ][] =
                            $this->src_dirname . $item[ 'name' ] . $item[ 'extension' ];

                    $item[ 'trackUrl' ] =
                        $this->moveFile(
                            $item[ 'id'        ],
                            $item[ 'name'      ],
                            $item[ 'extension' ]
                        );

                    $this->prepareUpdataData( $item );
                }
                else
                    $this->fialed_track_to[ 'inequal info and inserted record' ][] =
                            $this->src_dirname . $item[ 'name' ] . $item[ 'extension' ];
            }
            else
                $this->fialed_track_to[ 'lack of source file after insert data' ][] =
                        $this->src_dirname . $item[ 'name' ] . $item[ 'extension' ];
        }

        $this->doUpdate();
        $this->terminate( 'command done!' );
    }

    public function optionSupplier () {

        if (
            $this->path != ''   &&
            $this->path != null &&
            $this->path != 'null'
        ) {

            if ( is_file( $this->path ) ) {

                $pathinfo = pathinfo( $this->path );

                $filename          =       $pathinfo[ 'filename'  ];
                $extension         = '.' . $pathinfo[ 'extension' ];
                $this->src_dirname =       $pathinfo[ 'dirname'   ] . '/';

                $this->src_files[] = compact( 'filename', 'extension' );

            }
            else if ( is_dir( $this->path ) ) {

                $pathinfo = pathinfo( $this->path );

                $this->src_dirname =
                    $pathinfo[ 'dirname' ] .
                    '/' .
                    $pathinfo[ 'basename' ] .
                    '/';

                $pattern = '/^([^.])/';
                $src_files =
                    preg_grep( $pattern, scandir( $this->src_dirname ) );

                if (         
                             ( $src_files ) &&
                        count( $src_files ) &&
                      ! empty( $src_files ) &&
                    ! is_null( $src_files )
                ) {

                    foreach ( $src_files as $src_file ) {

                        $pathinfo = pathinfo( $src_file );

                        $filename  =       $pathinfo[ 'filename'  ];
                        $extension = '.' . $pathinfo[ 'extension' ];

                        $this->src_files[] = compact( 'filename', 'extension' );
                    }
                    $this->src_files = $this->getUniqueArray( $this->src_files );

                }
                else {

                    $msg = 'Files not found :' . "\n" . $this->path;
                    $this->terminate( $msg );
                }

            }
            else {

                $msg = 'File/Directory not exist :' . "\n" . $this->path;
                $this->terminate( $msg );
            }

            if ( ! isset( $this->src_files ) || count( $this->src_files ) <= 0 ) {

                $msg = 'Track(s) not found :' . "\n" . $this->path;
                $this->terminate( $msg );
            }

            $this->api  = new Api;
            $this->grep = new ID3;
            $this->client  = $this->api->client();

        }
        else {

            $msg = 'File/Directory required :' . "\n" . $this->path;
            $this->terminate( $msg );
        }
    }

    public function prepareInsertData()
    {
        foreach ( $this->src_files as $src_file ) {

            $name      = $src_file[ 'filename'  ];
            $extension = $src_file[ 'extension' ];
            $basename  = $name . $extension;

            /* Get src_file metadata */
            $file_info = $this->getMetadata( $name, $extension );
            if ( $file_info ) {

                /* Check if src_file's artist name not exist, skip it */
                if ( ! $artist = $this->hasArtist( $file_info ) ) {
                    $this->fialed_track_to[ 'lack of artist' ][] = $this->src_dirname . $basename;
                    continue;
                }

                $name =
                    isset ( $file_info[ 'filename' ] )
                    ? pathinfo( $file_info[ 'filename' ] )[ 'filename' ]
                    : $name;

                $name   = trim( $name   );
                $artist = trim( $artist );

                $this->insert_data[] = compact( 'name', 'artist', 'extension' );

            }
            else {

                $this->fialed_track_to[ 'lack of info' ][] = $this->src_dirname . $basename;
                continue;
            }
        }
    }

    public function doInsert()
    {
        if (
                   ( $this->insert_data ) ||
              empty( $this->insert_data ) ||
            ! count( $this->insert_data ) ||
            ! isset( $this->insert_data )
        )

        foreach ( $this->insert_data as $data ) {

            $name      = $data[ 'name'      ];
            $artist    = $data[ 'artist'    ];
            $extension = $data[ 'extension' ];

            try {
                if ( $this->debug )
                    $result = $this->locallyInsert( $name, $artist );
                else
                    $result = $this->remotelyInsert( $name, $artist );

                if (
                             ( $result ) &&
                        isset( $result ) &&
                      ! empty( $result ) &&
                    ! is_null( $result )
                ) {

                    $result[ 'extension' ] = $extension;
                    $this->insert_result[] = $result;

                }
                else {

                    $this->fialed_track_to[ 'problem in select\insert result' ][] =
                        $this->src_dirname . $name . $extension;
                    continue;
                }
            }
            catch ( Ex $e ) {
                $this->fialed_track_to[ 'query select\insert problem' ][] =
                    $this->src_dirname . $name . $extension;
                continue;
            }
        }
    }

    public function locallyUpdate( $id, $data )
    {
        $result =
            \DB::table( 'tracks' )
                ->where( 'id', $id )
                ->update( $data );

        if (
                     ( $result ) &&
                isset( $result ) &&
              ! empty( $result ) &&
            ! is_null( $result )
        ) {
            $result =
                $this->convertToArray(
                    \DB::table( 'tracks' )
                        ->where( 'id', $id )
                        ->first()
                );
        }

        return $result;
    }

    public function locallyInsert( $name, $artist )
    {
        $result =
            $this->convertToArray(
                \DB::table( 'tracks' )
                    ->where( [ [ 'name', $name ], [ 'artist', $artist ] ] )
                    ->first()
            );

        if (
                  ! ( $result )  ||
                empty( $result ) ||
              is_null( $result ) ||
              ! isset( $result )
        ) {
            \DB::table( 'tracks' )
                ->insert( compact( 'name', 'artist' ) );

            $result =
                $this->convertToArray(
                    \DB::table( 'tracks' )
                        ->where( [ [ 'name', $name ], [ 'artist', $artist ] ] )
                        ->first()
                );
        }

        return $result;
    }

    public function remotelyInsert( $name, $artist )
    {
        // TODO: Select from remote
        // TODO: Check result of select
        // TODO: Is necessary, insert into remote
        // TODO: Return result
    }

    public function remotelyUpdate( $id, $data )
    {
        // TODO: put to remote
        // TODO: Return result
    }
    public function convertToArray( $input )
    {
        if ( $input instanceof Coll )
            return
                $input->map(
                    function( $item )
                    {
                        return ( array ) $item;
                    }
                )->toArray()[ 0 ];

        elseif ( $input instanceof Std )
            return ( array ) $input;

        elseif ( is_array( $input ) )
            return $input;

        else
            return null;

    }
    public function prepareUpdataData( $data )
    {
        $updatable_columns =
            [
                'lyric',
                'stream',
                'genres',
                'duration',
                'description',
                'segmentlist',
                'publishedDate',
                'assignedUserId',
                'assignedUserName'
            ];

        $new_data =
            $this->fetchInfo( $data, $updatable_columns );

        if ( ! ( $data == $new_data ) )
            $this->update_data[] = $new_data;
    }

    public function doUpdate()
    {
        if (
                 ! ( $this->update_data ) ||
              empty( $this->update_data ) ||
            ! count( $this->update_data ) ||
            ! isset( $this->update_data )
        )
            $this->terminate( 'Nothing to do updating' );

        foreach ( $this->update_data as $data ) {

            $id        = $data[ 'id'        ];
            $name      = $data[ 'name'      ];
            $artist    = $data[ 'artist'    ];
            $extension = $data[ 'extension' ];

            unset(
                $data[ 'id'        ],
                $data[ 'name'      ],
                $data[ 'info'      ],
                $data[ 'artist'    ],
                $data[ 'extension' ]
            );

            try {
                if ( $this->debug )
                    $result = $this->locallyUpdate( $id, $data );
                else
                    $result = $this->remotelyUpdate( $id, $data );

                if (
                             ( $result ) &&
                        isset( $result ) &&
                      ! empty( $result ) &&
                    ! is_null( $result )
                ) {
                    $this->update_result[] = $result;
                }
                else {

                    $this->fialed_track_to[ 'update, because thay are up-to-date' ][] =
                        $this->src_dirname . $name . $extension;
                    continue;
                }
            }
            catch ( Ex $e ) {
                $this->fialed_track_to[ 'update data' ][] =
                    $this->src_dirname . $name . $extension;
                continue;
            }
        }
    }

    public function moveCover( $id, $name, $extension, $binary_cover )
    {
        if ( ! file_exists( $this->cover_dir ) ) {

            $this->fialed_track_to[
                'move cover. Cover directory not exists: ' . $this->cover_dir ][] =
                    $this->src_dirname . $name . $extension;

            return null;
        }
            
        $dest = $this->cover_dir . $id . '.jpg';

        if ( file_exists( $dest ) && $this->no_overwrite ) {

            $this->fialed_track_to[ 'move cover because file with same name exist' ][] =
                $this->src_dirname . $name . $extension;

            return null;
        }

        try {

            // TODO: Handle file permission problem for unlink().
            if ( file_exists( $dest ) )
                unlink( $dest );

            if ( $cover = @imagecreatefromstring( $binary_cover ) ) {
                imagejpeg( $cover, $dest, 100 );
                imagedestroy( $cover );
                return $dest;
            }

            $this->fialed_track_to[ 'create and move cover' ][] =
                $this->src_dirname . $name . $extension;

            return null;
        }
        catch ( Exception $e ) {

            $this->fialed_track_to[ 'create and move cover' ][] =
                $this->src_dirname . $name . $extension;

            return null;
        }
        return null;
    }

    public function moveFile( $id, $name, $extension )
    {
        if ( ! file_exists( $this->track_dir ) ) {

            $this->fialed_track_to[
                'move file. Track directory not exists: ' . $this->track_dir ][] =
                    $this->src_dirname . $name . $extension;

            return null;
        }

        $src  = $this->src_dirname . $name . $extension;
        $dest = $this->track_dir   . $id   . $extension;

        if ( file_exists( $dest ) && $this->no_overwrite ) {

            $this->fialed_track_to[ 'move file because file with same name exist' ][] =
                $this->src_dirname . $name . $extension;

            return null;
        }

        // TODO: Handle file permission problem for unlink().
        if ( file_exists( $dest ) )
            unlink( $dest );

        if ( @copy( $src, $dest ) )
            return $dest;

        $this->fialed_track_to[ 'move file' ][] =
            $this->src_dirname . $name . $extension;
    }

    public function hasBinaryCover( $file_info )
    {
        if (
            $file_info                                              &&
            isset( $file_info[ 'id3v2' ] )                          &&
            isset( $file_info[ 'id3v2' ][ 'APIC' ] )                &&
            isset( $file_info[ 'id3v2' ][ 'APIC' ][ 0 ] )           &&
            isset( $file_info[ 'id3v2' ][ 'APIC' ][ 0 ][ 'data' ] ) &&
            $cover = $file_info[ 'id3v2' ][ 'APIC' ][ 0 ][ 'data' ]
        )
           return $cover;

        return null;
    }

    public function getUniqueArray( $array )
    {
        return
            array_map(
                'unserialize',
                array_unique(
                    array_map(
                        'serialize',
                        $array
                    )
                )
            );
    }

    public function hasArtist( $file_info )
    {
        return
            $file_info                                                &&
            isset( $file_info[ 'tags' ]                             ) &&
            isset( $file_info[ 'tags' ][ 'id3v1' ]                  ) &&
            isset( $file_info[ 'tags' ][ 'id3v1' ][ 'artist' ]      ) &&
            isset( $file_info[ 'tags' ][ 'id3v1' ][ 'artist' ][ 0 ] )
            ? $file_info[ 'tags' ][ 'id3v1' ][ 'artist' ][ 0 ]
            : false;
    }

    public function getMetadata( $name, $extension )
    {
        $file_full_path = $this->src_dirname . $name . $extension;

        $file_info =
            $this->grep
                ->analyze( $file_full_path );

        ID3Lib::CopyTagsToComments( $file_info );

        return $file_info ?? false;
    }

    function array_flatten( $array )
    {
       $return = array();

       foreach ( $array as $key => $value )
           if ( is_array( $value ) )
                $return = array_merge( $return, array_flatten( $value ) );
            else
                $return[ $key ] = $value;

       return $return;
    }

    public function fetchInfo( $data, $updatable_columns )
    {
        $info = $this->array_flatten( $data[ 'info' ] );

        // Per column of record, assign default values to some column,
        // or, search in file's fetched info to assignment.
        foreach ( $updatable_columns as $column ) {

            if ( $column == 'stream' )
                $$column = $this->track_dir . 'stream/' . $data[ 'id' ] . '.aac';

            else if ( $column == 'segmentlist' )
                $$column = $this->track_dir . 'segment/' . $data[ 'id' ] . "/track.m3u8";

            else if ( $column == 'duration' )
                $$column = isset( $info[ 'playtime_string' ] ) ? $info[ 'playtime_string' ] : '';

            else if ( $column == 'assignedUserId' )
                $$column = 1;

            else if ( $column == 'assignedUserName' )
                $$column = 'root';

            else {
                $$column = isset( $info[ $column ] ) ? $info[ $column ] : '';
            }
        }

        // Creating the array of column/value pairs of the info,
        // which fetched from the resource file's data.
        $new_info = compact( $updatable_columns );

        // Update or create column/value pairs by the resource file's data
        foreach ( $new_info as $key => $value ) {

            // Validating the column's value which fetched from the table.
            $first_condition =
                         ( $data[ $key ] ) &&
                    isset( $data[ $key ] ) &&
                  ! empty( $data[ $key ] ) &&
                ! is_null( $data[ $key ] );

            // Validating the value which fetched from the resource file.
            $second_condition =
                             ( $value ) &&
                        isset( $value ) &&
                      ! empty( $value ) &&
                   !  is_null( $value ) &&
                $value != $data[ $key ];

            // Overwrite table column's data by resource file's data,
            // "IF" the resource data is prefered.
            $data[ $key ] =
                $first_condition
                ? (
                    $second_condition
                    ? $value
                    : $data[ $key ]
                )
                : $value;
        }

        return $data;
    }

    public function fetchValue( $needle, $haystack )
    {
        if ( $haystack instanceof \Illuminate\Support\Collection ) {

            $haystack =
                $haystack->map(
                    function( $item )
                    {
                        return ( array ) $item;
                    }
                )->toArray();

            return
                array_column( $haystack, $needle )[ 0 ];
        }
    }

    public function arrayConcat( $array )
    {
        $is_valid =  ( $array ) &&
                count( $array ) &&
              ! empty( $array ) &&
             is_array( $array ) &&
            ! is_null( $array );

        if ( $is_valid ) {
    
            $output = null;
            foreach ( $array as $item ) {
                $output .= ( $item . PHP_EOL );
            }
    
            return $output;
        }
    }

    public function terminate( $message = null )
    {
        $msg = 'Command died';

        if ( $message )
            $msg .= " with message which is '" . $message . "'.";

        $msg .= PHP_EOL;

        foreach ( $this->fialed_track_to as $key => $value )
            $msg .= ( 'fialed track to' . ' ' . $key . ' : ' . count( $value ) . PHP_EOL );

        die( $msg );
    }
}