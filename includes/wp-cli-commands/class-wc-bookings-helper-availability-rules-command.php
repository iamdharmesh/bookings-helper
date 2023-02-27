<?php
/**
 * The WP CLI command for handle commands for booking availability rules.
 *
 * @package Bookings Helper/ WP CLI commands
 * @since   x.x.x
 */

use WP_CLI\ExitException;

if( ! class_exists( 'WP_CLI_Command' ) ) {
	return;
}

/**
 * Class WC_Bookings_Helper_Availability_Rules_Command
 * @since x.x.x
 */
class WC_Bookings_Helper_Availability_Rules_Command extends WP_CLI_Command {
	/**
	 * Export global availability rules.
	 *
	 * ## OPTIONS
	 *
	 * [--dir=<absolute_path_to_dir>]
	 * : The directory path to export the booking products
	 *
	 * ## EXAMPLES
	 * wp booking-helper-availability-rules export
	 * wp booking-helper-availability-rules export --dir=/path/to/export
	 *
	 * @since x.x.x
	 *
	 * @param array $args       Subcommand args.
	 * @param array $assoc_args Subcommand assoc args.
	 *
	 * @return void
	 * @throws ExitException
	 */
	public function export( array $args, array $assoc_args ) {
		// Default path is wp-content/uploads.
		$directory_path = empty( $assoc_args['dir'] ) ?
			trailingslashit( WP_CONTENT_DIR ) . 'uploads' :
			$assoc_args['dir'];

		try {
			$name_prefix = 'bookings-global-rules';

			$zip_file_path  = "$directory_path/$name_prefix.zip";
			$json_file_name = "$name_prefix.json";

			// Create zip;
			$zip = new ZipArchive();
			$zip->open( $zip_file_path, ZipArchive::CREATE | ZipArchive::OVERWRITE );

			$zip->addFromString(
				$json_file_name,
				// Get json data for all booking products.
				( new WC_Bookings_Helper_Export() )->get_global_availability_rules()
			);

			$zip->close();

			if ( $zip->open( $zip_file_path ) !== true ) {
				WP_CLI::error( 'Booking global availability rules export failed.' );

				return;
			}

			WP_CLI::success( "Booking global availability rules exported. Location: $zip_file_path" );
		} catch ( Exception $e ) {
			WP_CLI::error( $e->getMessage() );
		}
	}

	/**
	 * Import global availability rules.
	 *
	 * ## OPTIONS
	 *
	 * [--file=<absolute_path_to_zip_file>]
	 * : The zip file path to import the booking global availability rules
	 *
	 * ## EXAMPLES
	 * wp booking-helper-availability-rules import --file=/path/to/absolute_path_to_zip_file
	 *
	 * @since x.x.x
	 *
	 * @param array $args       Subcommand args.
	 * @param array $assoc_args Subcommand assoc args.
	 *
	 * @return void
	 * @throws ExitException
	 */
	public function import( array $args, array $assoc_args ) {
		if( empty( $assoc_args['file'] ) ) {
			WP_CLI::error( 'Please provide the zip file path to import the booking global availability rules.' );
			return;
		}

		$file_path = $assoc_args['file'];
		$file_name = basename( $assoc_args['file'], '.zip' );
		$json_file_path = dirname( $file_path ) . '/' . $file_name . '.json';
		$zip = new ZipArchive();

		if( $zip->open( $assoc_args['file'] ) !== true ) {
			WP_CLI::error( 'Booking global availability rules import failed. Please provide valid file path.' );
			return;
		}

		$zip->extractTo( dirname( $file_path ) );
		$zip->close();

		$availability_rules = file_get_contents($json_file_path);
		unlink( $json_file_path );

		if( empty( $availability_rules ) ) {
			WP_CLI::error( 'Booking global availability rules import failed. File does not have data to import.' );
			return;
		}

		( new WC_Bookings_Helper_Import() )->import_rules_from_json($availability_rules);
		WP_CLI::success( 'Booking global availability rules imported successfully.' );
	}
}
