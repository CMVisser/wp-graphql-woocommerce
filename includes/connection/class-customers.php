<?php
/**
 * Connection type - Customers
 *
 * Registers connections to Customers
 *
 * @package WPGraphQL\WooCommerce\Connection
 */

namespace WPGraphQL\WooCommerce\Connection;

use GraphQL\Type\Definition\ResolveInfo;
use WPGraphQL\AppContext;
use WPGraphQL\Data\Connection\UserConnectionResolver;
use WPGraphQL\WooCommerce\Data\Factory;

/**
 * Class - Customers
 */
class Customers {

	/**
	 * Registers the various connections from other Types to Customer
	 */
	public static function register_connections() {
		register_graphql_connection(
			array(
				'fromType'      => 'RootQuery',
				'toType'        => 'Customer',
				'fromFieldName' => 'customers',
				'connectionArgs' => self::get_connection_args(),
				'resolve'        => function ( $source, array $args, AppContext $context, ResolveInfo $info ) {
					$resolver = new UserConnectionResolver( $source, $args, $context, $info );

					if ( ! self::should_execute() ) {
						return array(
							'nodes' => array(),
							'edges' => array(),
						);
					}

					$resolver->set_query_arg( 'role', 'customer' );

					return $resolver->get_connection();
				},
			)
		);

		register_graphql_connection(
			array(
				'fromType'      => 'Coupon',
				'toType'        => 'Customer',
				'fromFieldName' => 'usedBy',
				'connectionArgs' => self::get_connection_args(),
				'resolve'        => function ( $source, array $args, AppContext $context, ResolveInfo $info ) {
					$resolver = new UserConnectionResolver( $source, $args, $context, $info );

					$resolver->set_query_arg( 'include', $source->used_by_ids );
					$resolver->set_query_arg( 'role', 'customer' );

					if ( ! self::should_execute() ) {
						return array();
					}

					return $resolver->get_connection();
				},
			)
		);
	}

	/**
	 * Confirms the uses has the privileges to query Customer
	 *
	 * @return bool
	 */
	public static function should_execute() {
		switch ( true ) {
			case current_user_can( 'list_users' ):
				return true;
			default:
				return false;
		}
	}

	/**
	 * Returns array of where args.
	 *
	 * @return array
	 */
	public static function get_connection_args(): array {
		return array(
			'search'    => array(
				'type'        => 'String',
				'description' => __( 'Limit results to those matching a string.', 'wp-graphql-woocommerce' ),
			),
			'exclude'   => array(
				'type'        => array( 'list_of' => 'Int' ),
				'description' => __( 'Ensure result set excludes specific IDs.', 'wp-graphql-woocommerce' ),
			),
			'include'   => array(
				'type'        => array( 'list_of' => 'Int' ),
				'description' => __( 'Limit result set to specific ids.', 'wp-graphql-woocommerce' ),
			),
			'email'     => array(
				'type'        => 'String',
				'description' => __( 'Limit result set to resources with a specific email.', 'wp-graphql-woocommerce' ),
			),
			'orderby'   => array(
				'type'        => 'CustomerConnectionOrderbyEnum',
				'description' => __( 'Order results by a specific field.', 'wp-graphql-woocommerce' ),
			),
			'order'     => array(
				'type'        => 'OrderEnum',
				'description' => __( 'Order of results.', 'wp-graphql-woocommerce' ),
			),
		);
	}

	/**
	 * This allows plugins/themes to hook in and alter what $args should be allowed to be passed
	 * from a GraphQL Query to the WP_Query
	 *
	 * @param array              $query_args The mapped query arguments.
	 * @param array              $where_args       Query "where" args.
	 * @param mixed              $source     The query results for a query calling this.
	 * @param array              $args   All of the arguments for the query (not just the "where" args).
	 * @param AppContext         $context    The AppContext object.
	 * @param ResolveInfo        $info       The ResolveInfo object.
	 *
	 * @return array Query arguments.
	 */
	public static function map_input_fields_to_wp_query( $query_args, $where_args, $source, $args, $context, $info ) {

		$key_mapping = array(
			'search'    => 'search',
			'exclude'   => 'exclude',
			'include'   => 'include',
		);

		foreach ( $key_mapping as $key => $field ) {
			if ( ! empty( $where_args[ $key ] ) ) {
				$query_args[ $field ] = $where_args[ $key ];
			}
		}

		// Filter by email.
		if ( ! empty( $where_args['email'] ) ) {
			$query_args['search']         = $where_args['email'];
			$query_args['search_columns'] = array( 'user_email' );
		}

		/**
		 * Map the orderby inputArgs to the WP_Query
		 */
		if ( ! empty( $where_args['orderby'] ) ) {
			$query_args['orderby'] = $where_args['orderby'];
		}

		/**
		 * Map the orderby inputArgs to the WP_Query
		 */
		if ( ! empty( $where_args['order'] ) ) {
			$query_args['order'] = $where_args['order'];
		}

		/**
		 * Filter the input fields
		 * This allows plugins/themes to hook in and alter what $args should be allowed to be passed
		 * from a GraphQL Query to the WP_Query
		 *
		 * @param array       $args       The mapped query arguments
		 * @param array       $where_args Query "where" args
		 * @param mixed       $source     The query results for a query calling this
		 * @param array       $all_args   All of the arguments for the query (not just the "where" args)
		 * @param AppContext  $context    The AppContext object
		 * @param ResolveInfo $info       The ResolveInfo object
		 */
		$query_args = apply_filters(
			'graphql_map_input_fields_to_customer_query',
			$query_args,
			$where_args,
			$source,
			$args,
			$context,
			$info
		);

		return $query_args;
	}
}
