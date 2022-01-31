<?php

declare (strict_types = 1);

namespace Codilar\CustomerGraphQl\Model\Resolver;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\Api\SortOrderBuilder;

class CustomerOrder implements ResolverInterface {
   /**
    * @var orderRepository
    */
    protected $orderRepository;

    /**
     * @var searchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @var priceCurrency
     */
    protected $priceCurrency;

      /**
     * SortOrder builder
     * @var SortOrderBuilder
     */
    private $sortOrderBuilder;

    public function __construct(
       \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
       \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
       \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
       SortOrderBuilder $sortOrderBuilder
    ) {
       $this->orderRepository = $orderRepository;
       $this->searchCriteriaBuilder = $searchCriteriaBuilder;
       $this->priceCurrency = $priceCurrency;
       $this->sortOrderBuilder = $sortOrderBuilder;
    }
    /**
     * @inheritdoc
     */
    public function resolve(
       Field $field,
       $context,
       ResolveInfo $info,
       array $value = null,
       array $args = null
    ) {

      $customerOrderData = $this->getCustomerOrderData();
      return $customerOrderData;

    }
    /**
     * @param int $customerId
     * @return array
     * @throws GraphQlNoSuchEntityException
     */
    private function getCustomerOrderData(): array
    {
       try {
           $searchCriteria = $this->searchCriteriaBuilder->addFilter('customer_id', 1, 'gteq');
           $sortOrder = $this->sortOrderBuilder
           ->setField('total_qty_ordered')
           ->setDirection(SortOrder::SORT_DESC)
           ->create();
           $this->searchCriteriaBuilder->addSortOrder($sortOrder)->setPageSize(5)
           ->setCurrentPage(1);
           $searchCriteria = $this->searchCriteriaBuilder->create();
           $orderList = $this->orderRepository->getList($searchCriteria);
           $customerOrder = [];
           foreach ($orderList as $order) {
               $order_id = $order->getId();
               $customerOrder['fetchRecords'][$order_id]['increment_id'] = $order->getIncrementId();
               $customerOrder['fetchRecords'][$order_id]['customer_name'] = $order->getCustomerFirstname() . ' ' . $order->getCustomerLastname();
               $customerOrder['fetchRecords'][$order_id]['grand_total'] = $this->priceCurrency->convertAndFormat($order->getGrandTotal(), false);
               $customerOrder['fetchRecords'][$order_id]['qty'] = $order->getTotalQtyOrdered();
           }
       } catch (NoSuchEntityException $e) {
           throw new GraphQlNoSuchEntityException(__($e->getMessage()), $e);
       }
       return $customerOrder;
    }
}