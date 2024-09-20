<?php
/**
 *
 * WC_Wallee_Service_Method_Configuration Class
 *
 * Wallee
 * This plugin will add support for all Wallee payments methods and connect the Wallee servers to your WooCommerce webshop (https://www.wallee.com).
 *
 * @category Class
 * @package  Wallee
 * @author   wallee AG (https://www.wallee.com)
 * @license  http://www.apache.org/licenses/LICENSE-2.0 Apache Software License (ASL 2.0)
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}
/**
 * WC_Wallee_Service_Method_Configuration Class.
 */
class WC_Wallee_Service_Method_Configuration extends WC_Wallee_Service_Abstract {

	/**
	 * Updates the data of the payment method configuration.
	 *
	 * @param \Wallee\Sdk\Model\PaymentMethodConfiguration $configuration configuration.
	 * @throws Exception Exception.
	 */
	public function update_data( \Wallee\Sdk\Model\PaymentMethodConfiguration $configuration ) {
		/* @var WC_Wallee_Entity_Method_Configuration $entity */
		$entity = WC_Wallee_Entity_Method_Configuration::load_by_configuration( $configuration->getLinkedSpaceId(), $configuration->getId() );
		if ( $entity->get_id() !== null && $this->has_changed( $configuration, $entity ) ) {
			$entity->set_configuration_name( $configuration->getName() );
			$entity->set_title( $configuration->getResolvedTitle() );
			$entity->set_description( $configuration->getResolvedDescription() );
			$entity->set_image( $this->get_resource_path( $configuration->getResolvedImageUrl() ) );
			$entity->set_image_base( $this->get_resource_base( $configuration->getResolvedImageUrl() ) );
			$entity->set_state( $this->get_configuration_state( $configuration ) );
			$entity->save();
		}
	}

	/**
	 * Has changed.
	 *
	 * @param \Wallee\Sdk\Model\PaymentMethodConfiguration $configuration configuration.
	 * @param WC_Wallee_Entity_Method_Configuration        $entity entity.
	 * @return bool
	 */
	private function has_changed( \Wallee\Sdk\Model\PaymentMethodConfiguration $configuration, WC_Wallee_Entity_Method_Configuration $entity ) {

		if ( $this->get_configuration_state( $configuration ) != $entity->get_state() ) {
			return true;
		}
		if ( $configuration->getName() != $entity->get_configuration_name() ) {
			return true;
		}
		if ( $configuration->getResolvedTitle() != $entity->get_title() ) {
			return true;
		}
		if ( $configuration->getResolvedDescription() != $entity->get_description() ) {
			return true;
		}
		if ( $this->get_resource_path( $configuration->getResolvedImageUrl() ) != $entity->get_image() ) {
			return true;
		}
		if ( $this->get_resource_base( $configuration->getResolvedImageUrl() ) != $entity->get_image_base() ) {
			return true;
		}
		return false;
	}

	/**
	 * Synchronizes the payment method configurations from wallee.
	 */
	public function synchronize() {
		$existing_found = array();
		$space_id = get_option( WooCommerce_Wallee::CK_SPACE_ID );

		$existing_configurations = WC_Wallee_Entity_Method_Configuration::load_all();

		if ( ! empty( $space_id ) ) {
			$payment_method_configuration_service = new \Wallee\Sdk\Service\PaymentMethodConfigurationService(
				WC_Wallee_Helper::instance()->get_api_client()
			);
			$configurations = $payment_method_configuration_service->search(
				$space_id,
				new \Wallee\Sdk\Model\EntityQuery()
			);

			foreach ( $configurations as $configuration ) {
				/* @var WC_Wallee_Entity_Method_Configuration $method */
				$method = WC_Wallee_Entity_Method_Configuration::load_by_configuration( $space_id, $configuration->getId() );
				if ( $method->get_id() !== null ) {
					$existing_found[] = $method->get_id();
				}

				$method->set_space_id( $space_id );
				$method->set_configuration_id( $configuration->getId() );
				$method->set_configuration_name( $configuration->getName() );
				$method->set_state( $this->get_configuration_state( $configuration ) );
				$method->set_title( $configuration->getResolvedTitle() );
				$method->set_description( $configuration->getResolvedDescription() );

				$method->set_image( $this->get_resource_path( $configuration->getResolvedImageUrl() ) );
				$method->set_image_base( $this->get_resource_base( $configuration->getResolvedImageUrl() ) );
				$method->save();
			}
		}
		foreach ( $existing_configurations as $existing_configuration ) {
			if ( ! in_array( $existing_configuration->get_id(), $existing_found ) ) {
				$existing_configuration->set_state( WC_Wallee_Entity_Method_Configuration::STATE_HIDDEN );
				$existing_configuration->save();
			}
		}
		delete_transient( 'wc_wallee_payment_methods' );
	}


	/**
	 * Returns the payment method for the given id.
	 *
	 * @param int $id id.
	 * @return \Wallee\Sdk\Model\PaymentMethod
	 */
	protected function get_payment_method( $id ) {
		/* @var WC_Wallee_Provider_Payment_Method */
		$method_provider = WC_Wallee_Provider_Payment_Method::instance();
		return $method_provider->find( $id );
	}

	/**
	 * Returns the state for the payment method configuration.
	 *
	 * @param \Wallee\Sdk\Model\PaymentMethodConfiguration $configuration configuration.
	 * @return string
	 */
	protected function get_configuration_state( \Wallee\Sdk\Model\PaymentMethodConfiguration $configuration ) {
		switch ( $configuration->getState() ) {
			case \Wallee\Sdk\Model\CreationEntityState::ACTIVE:
				return WC_Wallee_Entity_Method_Configuration::STATE_ACTIVE;
			case \Wallee\Sdk\Model\CreationEntityState::INACTIVE:
				return WC_Wallee_Entity_Method_Configuration::STATE_INACTIVE;
			default:
				return WC_Wallee_Entity_Method_Configuration::STATE_HIDDEN;
		}
	}
}
