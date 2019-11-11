<?php

namespace App\Console\Commands;

use \App\Grep\Entity\Api;
use Illuminate\Console\Command;
use \Illuminate\Database\QueryException;
use \FFMpeg\FFProbe;

class UploadTrackMoveTrack extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrate:uploadtracktotrack';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'upload track to track';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {

        try {
            $list =
                \DB::table( 'upload_track' )
                    ->where( 'deleted', 0 )
                    ->get();
        }
        catch ( QueryException $e ) {
            dd( $e->getMessage() );
        }

        $api_client = ( new Api() )->client();
        $success_ids = $failed_to_copy_file = $failed_to_request = $data = [];

        $path        = "/home/app/music/repository/";
        $upload_path = "/home/app/music/api/data/upload/";

        foreach ( $list as $track ) {

            if ( isset( $track->file_id ) && isset( $track->cover_id ) ) {

                $mp3_id   = $track->file_id;
                $cover_id = $track->cover_id;

                $mp3_file   = $upload_path . $mp3_id;
                $cover_file = $upload_path . $cover_id;

                $file_source  = $path . "track/" . $mp3_id   . ".mp3";
                $cover_source = $path . "cover/" . $cover_id . ".jpg";

                unset( $result, $output );

                exec( 'cp ' . $mp3_file   . " " . $file_source,  $output, $result );
                exec( 'cp ' . $cover_file . " " . $cover_source, $output, $result );

                if ( $result === 0 ) {

                    $ffprobe = FFProbe::create();
                    @$duration =  @$ffprobe->format( $file_source )->get( 'duration' );
                    $duration = $duration ?? 0;

                    $data[ 'img'              ] = "/cover/" . $cover_id . ".jpg";
                    $data[ 'stream'           ] = "/track/stream/" . $mp3_id . ".aac";
                    $data[ 'trackUrl'         ] = "/track/" . $mp3_id . ".mp3";
                    $data[ 'segmentlist'      ] = "/track/segment/" . $mp3_id . "/track.m3u8";
                    $data[ 'name'             ] = isset( $track->name ) ? $track->name : '';
                    $data[ 'lyric'            ] = isset( $track->description ) ? $track->description : '';
                    $data[ 'description'      ] = $data[ 'lyric' ];
                    $data[ 'genres'           ] = isset( $track->genres ) ? $track->genres : '';
                    $data[ 'publishedDate'    ] = isset( $track->published_date ) ? $track->published_date : '';
                    $data[ 'duration'         ] = $duration;
                    $data[ 'assignedUserId'   ] = 1;
                    $data[ 'assignedUserName' ] = 'root';

                    try {
                        $new_track = $api_client->request( 'POST', 'track', $data );
                        $success_ids[] = $track->id;
                    }
                    catch ( \Exception $e ) {
                        $failed_to_request[] =
                            [
                                'track_id' => $track->id,
                                'request_exception' => $e->getMessage()
                            ];
                    }
                } else {
                    $failed_to_copy_file[] = $track->file_id;
                }
            }
        }

        if ( $success_ids ) {
            try {
                \DB::table( 'upload_track' )
                    ->whereIn( 'id', $success_ids )
                    ->update( 'deleted', 1 );
            }
            catch ( QueryException $e ) {
                dd( $e->getMessage() ); 
            }
        }

        $this->info(
            'Succeeded tracks to move : '    . count( $success_ids         ) . PHP_EOL .
            'Failed tracks to request : '    . count( $failed_to_request   ) . PHP_EOL .
            'Failed tracks to copy files : ' . count( $failed_to_copy_file )
        );
    }
}
