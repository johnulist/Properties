<?php
App::uses('PropertiesAppController', 'Properties.Controller');
/**
 * Properties Controller
 *
 * Handles the logic for properties.
 *
 * PHP versions 5
 *
 * Zuha(tm) : Business Management Applications (http://zuha.com)
 * Copyright 2009-2012, Zuha Foundation Inc. (http://zuha.org)
 *
 * Licensed under GPL v3 License
 * Must retain the above copyright notice and release modifications publicly.
 *
 * @copyright     Copyright 2009-2012, Zuha Foundation Inc. (http://zuha.com)
 * @link          http://zuha.com Zuha� Project
 * @package       zuha
 * @subpackage    zuha.app.plugins.properties
 * @since         Zuha(tm) v 0.0.1
 * @license       GPL v3 License (http://www.gnu.org/licenses/gpl.html) and Future Versions
 */
class AppPropertiesController extends PropertiesAppController {

/**
 * Name
 *
 * @var string
 */
	public $name = 'Properties';

/**
 * Uses
 *
 * @var string
 */
	public $uses = 'Properties.Property';
	
	public $components = array('Paginator');
	

/**
 * Properties dashboard.
 *
 */
	public function dashboard(){
		$this->set('properties', $this->request->data = $this->paginate());
		return $this->request->data;
        $this->layout = 'default';
	}

/**
 * Index method.
 *
 * @param void
 * @return void
 */
	public function index() {
		if(isset($this->request->query['search']) && !empty($this->request->query['search'])) {
			$query = $this->request->query['search'];
			$this->Paginator->settings = array(
				'conditions' => array(
					'OR' => array(
						'Property.search_tags LIKE' => "%$query%",
						'Property.location LIKE' => "%$query%",
						'Property.name LIKE' => "%$query%",
						'Property.description LIKE' => "%$query%",		
					)
			));
		}elseif(isset($this->request->query['advanced']) && !empty($this->request->query['advanced'])) {
			$conditions = array();
			$queries = $this->request->query;
			foreach ($queries as $param => $query) {
				if(!empty($query)) {
					if(strpos($param, 'category') !== false) {
						$this->loadModel('Categories.Categorized');
						$categories = $this->Categorized->find('all', array('conditions' => array('Categorized.category_id' => $query, 'Categorized.model' > 'Property')));
						if(!empty($categories)) {
							if(isset($conditions['Property.id'])) {
								$conditions['Property.id'] += Hash::extract($categories, '{n}.Categorized.foreign_key');
							}else {
								$conditions['Property.id'] = Hash::extract($categories, '{n}.Categorized.foreign_key');
							}
						}
					}else {
						switch ($param) {
							case 'name':
								$conditions['Property.name LIKE'] = "%$query%";
								break;
							case 'descritpion':
								$conditions['Property.description LIKE'] = "%$query%";
								break;
							case 'price_min':
								if(!empty($queries['price_max'])) {
									$conditions['Property.price BETWEEN ? AND ?'] = array($queries['price_min'], $queries['price_max']);
								}else {
									$conditions['Property.price >'] = "%$query%";
								}
								break;
							case 'price_max':
								if(empty($queries['price_min'])) {
									$conditions['Property.price <'] = "%$query%";
								}
								break;
							case 'bedrooms':
								foreach ($query as $q) {
									$range = explode('_', $q);
									if($range[1] == '+') {
										$conditions['Property.bedrooms >'] = $range[0];
									}else {
										$conditions['Property.bedrooms BETWEEN ? AND ?'] = $range;
									}
								}
								break;
							case 'bathrooms':
								foreach ($query as $q) {
									$range = explode('_', $q);
									if($range[1] == '+') {
										$conditions['Property.bathrooms >'] = $range[0];
									}else {
										$conditions['Property.bathrooms BETWEEN ? AND ?'] = $range;
									}
								}
								break;
							case 'footage':
								foreach ($query as $q) {
									$range = explode('_', $q);
									if($range[1] == '+') {
										$conditions['Property.footage >'] = $range[0];
									}else {
										$conditions['Property.footage BETWEEN ? AND ?'] = $range;
									}
								}
								break;
							case 'acres':
								foreach ($query as $q) {
									$range = explode('_', $q);
									if($range[1] == '+') {
										$conditions['Property.acres >'] = $range[0];
									}else {
										$conditions['Property.acres BETWEEN ? AND ?'] = $range;
									}
								}
								break;
						}
					}
				}
			}
			$this->Paginator->settings = array(
				'conditions' => array(
					'OR' => $conditions,
			));
		}
		
		$this->set('properties', $this->request->data = $this->Paginator->paginate('Property'));
		
		return $this->request->data;
	}

/**
 * Category method.
 *
 * @param void
 * @return void
 */
	public function category($categoryId = null) {
		if (!empty($categoryId)) {
			$this->paginate['joins'] = array(array(
				'table' => 'categorized',
				'alias' => 'Categorized',
				'type' => 'INNER',
				'conditions' => array(
					"Categorized.foreign_key = Property.id",
					"Categorized.model = 'Property'",
					"Categorized.category_id = '{$categoryId}'",
				),
			));
			$this->paginate['contain'][] = 'Category';
		} 
		$this->view = 'index';
		return $this->index();
	}


/**
 * View method
 * 
 */
	public function view($id = null) {
		$this->Property->id = $id;
		if (!$this->Property->exists()) {
			throw new NotFoundException(__('Invalid Property'));
		}
              
		$property = $this->Property->find('first' , array(
			'conditions' => array(
				'Property.id' => $id
				)
			));
			
		$this->set('property', $this->request->data = $this->Property->find('first' , array(
			'conditions' => array(
				'Property.id' => $id
				)
			)));
        $this->set('title_for_layout', $this->request->data['Property']['name']);
        return $this->request->data;
	}
	
	/**
	 * Featured method
	 *
	 */
	public function featured() {
		$this->request->data = $this->Property->find('all' , array(
				'conditions' => array(
						'Property.is_featured' => true
				),
				'contain' => array('Media'),
		));
			
		$this->set('title_for_layout', $this->request->data['Property']['name']);
		return $this->request->data;
	}
	

/**
 * Add a property
 * 
 */
    public function add() {
    	if (!empty($this->request->data)) {
			if ($this->Property->saveAll($this->request->data)) {
				$this->Session->setFlash(__('Property saved.'));
				$this->redirect(array('action' => 'edit', $this->Property->id));
            } 
		}
    	if (in_array('Categories', CakePlugin::loaded())) {
        	$this->set('categories', $this->Property->Category->generateTreeList());
		}
		$this->set('page_title_for_layout', __('Create a Property'));
		$this->set('title_for_layout', __('Add Property Form'));
        $this->layout = 'default';
    }

    
/**
 * Edit method
 *
 * @access public
 * @param type $id
 * @throws NotFoundException
 */
	public function edit($id = null) {
		$this->redirect('admin');
		$this->Property->id = $id;
		if (!$this->Property->exists()) {
			throw new NotFoundException(__('Invalid property'));
		}
		
		if (!empty($this->request->data)) {
			if ($this->Property->saveAll($this->request->data)) {
				$this->Session->setFlash(__('Property saved.'));
				if (isset($this->request->data['SaveAndContinue'])) {
					$this->redirect(array('action' => 'edit', $this->Property->id));
				} else {
					$this->redirect(array('action' => 'view', $this->Property->id, 'admin' => false));
				}
            }
		} else {
	        $this->request->data = $this->Property->find('first', array(
	            'conditions' => array(
	                'Property.id' => $id
	                )
				));
		}
		if (CakePlugin::loaded('Categories')) {
	        $this->set('categories', $this->Property->Category->generateTreeList());
			$selectedCategories = $this->Property->Category->Categorized->find('all', array(
				'conditions' => array(
					'Categorized.model'=>$this->Property->alias,
					'Categorized.foreign_key'=>$this->Property->id
					),
				'contain' => array('Category')
				));
			$this->set('selectedCategories',  Set::extract($selectedCategories, '/Category/id'));
		}
       	$this->set('page_title_for_layout', __('Edit %s ', $this->request->data['Property']['name']));
		$this->set('title_for_layout', __('Edit %s ', $this->request->data['Property']['name']));
	}

/**
 * Delete method
 * 
 * @param string $id
 */
	public function delete($id = null) {
		$this->Property->id = $id;
		if (!$this->Property->exists()) {
			throw new NotFoundException(__('Invalid property'));
		}
		debug('this needs to be post only, fix');
		break;
	}
    
/**
 * Categories method
 * 
 */
    public function categories() {
        if (!empty($this->request->data['Category'])) {
            if ($this->Property->Category->save($this->request->data)) {
                $this->Session->setFlash(__('Category saved'));
            }
        }

        $this->set('categories', $categories = $this->Property->Category->find('threaded'));
        $this->set('parentCategories', Set::combine($categories, '{n}.Category.id', '{n}.Category.name'));
        $this->set('page_title_for_layout', __('Property Categories'));
		$this->layout = 'default';
    }
    
    /**
     * Advanced Search Page
     * 
     */
    
    public function advanced_search() {
    	$acre_options = array(
    		'0_1' => 'Less than 1',
    		'1_2' => '1 to 2',
    		'3_5' => '3 to 5',
    		'5_10' => '5 to 10',
    		'10_+' => 'More than 10'
    	);
    	$bedroom_options = array(
    			'1_2' => '1 to 2',
    			'2_3' => '2 to 3',
    			'3_4' => '4 to 5',
    			'5_+' => 'More than 5'
    	);
    	$bathroom_options = array(
    			'1_2' => '1 to 2',
    			'2_3' => '2 to 3',
    			'3_+' => 'More than 4'
    	);
    	$footage_options = array(
    			'0_1000' => 'Less than 1000',
    			'1001_2000' => '1000 to 2000',
    			'2001_3000' => '2001 to 3000',
    			'3001_4000' => '3001 to 4000',
    			'4001_5000' => '4001 to 5000',
    			'5000_+' => '5000+',
    	);
    	if(CakePlugin::loaded('Categories')) {
    		$catlist = array();
    		$categories = $this->Property->Category->find('threaded', array('conditions' => array('Category.model' => 'Property')));
    		foreach ($categories as $parent) {
    			$catlist[$parent['Category']['name']] = array();
    			foreach ($parent['children'] as $child) {
    				$catlist[$parent['Category']['name']][$child['Category']['id']] = $child['Category']['name'];
    			}
    		}
    		$this->set('categories', $catlist);
    	}
    	$this->set(compact('acre_options', 'bedroom_options', 'bathroom_options', 'footage_options'));
    }
}

if (!isset($refuseInit)) {
	class PropertiesController extends AppPropertiesController {
	}

}