<?php
namespace Drupal\hello\Controller;
use cebe\markdown\Markdown;
use Drupal\Core\Controller\ControllerBase;
use Drupal\hello\Quote;
use Drupal\hello\Voles;
use Drupal\user\Entity\Role;
use Gregwar\RST\Directives\DangerBlock;
use Symfony\Component\DependencyInjection\ContainerInterface;
//use Symfony\Component\HttpFoundation\RedirectResponse;

use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Drupal\hello\ElExercise;

use Drupal\hello\Rst\Puppy;

use Gregwar\RST\Parser as RestParser;

use Netcarver\Textile\Parser as TextileParser;

use Drupal\hello\SkillCourseParser;

/**
 * Controller for the salutation message.
 */
class HelloController extends ControllerBase {

  /**
   * @var \Drupal\hello\Quote
   */
  protected $quoteService;

  /**
   * @var \Drupal\hello\SkillCourseParser
   */
  protected $parserService;

  /**
   * HelloController constructor.
   *
   * @param \Drupal\hello\Quote $qs
   * @param \Drupal\hello\SkillCourseParser $ps
   * @internal param \Drupal\hello\Quote $quoteService
   */
  public function __construct(Quote $qs, SkillCourseParser $ps) {
    $this->quoteService = $qs;
    $this->parserService = $ps;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('hello.quote'),
      $container->get('hello.skillcourseparser')
    );
  }

  /**
   * Hello World.
   *
   * @return string
   */
  public function hello() {
    $q = $this->quoteService->tellMe();
    return [
      '#markup' => $this->t('Hello. ' . $q)
    ];
  }

  public function helloDog($name) {
    return [
      '#markup' => "<p>Hello, $name!</p>",
    ];
  }

  public function helloRosie() {
    return $this->redirect('hello.dog', ['name' => 'Rrosie']);
    //return new RedirectResponse('/dog/Rosie');
  }

  public function el1() {

    $language = new ExpressionLanguage();

    $elExs = [];
    $elExs['ex1'] = new ElExercise();
    $elExs['ex1']->submitted = FALSE;
    $elExs['ex1']->completed = FALSE;
    $expression = 'exercises["ex1"].submitted';
    $context = [
      'exercises' => $elExs,
    ];
    $msg = $language->evaluate($expression, $context) ? 'Ex1 submitted' : 'Ex1 not submitted';
//      $expression = 'user["isActive"] == true and product["price"] > 20';
//      $context = array(
//        'user' => array(
//          'isActive' => true
//        ),
//        'product' => array(
//          'price' => 30
//        ),
//      );
//
//      $return = $language->evaluate($expression, $context);
//
//      $r = $return ? 'Yes' : 'No';
    return [
      '#markup' => $this->t('El1 ' . $msg)
    ];
  }


  public function el2() {

    $language = new ExpressionLanguage();

    $elExs = [];
    $elExs['ex1'] = new ElExercise();
    $elExs['ex1']->submitted = FALSE;
    $elExs['ex1']->completed = FALSE;
    $expression = 'exercises["ex1"].submitted';
    $context = [
      'exercises' => $elExs,
    ];
    $msg = $language->evaluate($expression, $context) ? 'Ex1 submitted' : 'Ex1 not submitted';
  }


    public function rst1() {

      $parser = new RestParser();
      $parser->registerDirective(new DangerBlock());
      $parser->registerDirective(new Puppy());

// RST document
      $rst = ' 
Hello world
===========

Dogs!!

.. danger:: butts
   :thing: big
   Beware killer rabbits!

.. puppy:: hide

    This is puppy content.
    
    So is **this**.
 
    .. code-block:: php
    
        <?php
    
        echo "Hello world!\n";
 
 
What is it?
----------
This is a **RST** document!

+------------+------------+-----------+
| Header 1   | Header 2   | Header 3  |
+============+============+===========+
| body row 1 | column 2   | column 3  |
+------------+------------+-----------+
| body row 2 | Cells may  | span      |
+------------+------------+-----------+
| body row 3 | Cells may  | - Cells   |
+------------+------------+-----------+
| body row 4 |       span | rows.     |
+------------+------------+-----------+

.. raw:: html

    <h2>Geckos</h2>
    


Where can I get it?
-------------------
You can get it on the `GitHub page <https://github.com/Gregwar/RST>`_
';

// Parse it
      $document = $parser->parse($rst);
      return [
        '#markup' => $document,
      ];

    }


  public function cebe1() {
    $parser = new Markdown();
    $content = "
# This is an H1

## This is an H2
    
This is some stuff.
    
    ";
    $result = $parser->parse($content);

    return [
      '#markup' => $result,
    ];
    
    
    
  }

  public function cebe2() {
    $parser = new Voles();
    $content = "
# This is an H1

## This is an H2 for a vole

vole

# Another h2

Here is some voley stuff.
endvole
    
This is some stuff here.
    
    ";
    $result = $parser->parse($content);

    return [
      '#markup' => $result,
    ];



  }

  public function textile1() {
    $p = new TextileParser('html5');
    $source = "
h1. This thing!
      
      
Dogs are the *best*!
";
    $result = $p->parse($source);
    return [
      '#markup' => $result,
    ];

  }

  public function textile2() {
    $source = "
  h1{border:5px solid red;}. This thing
      
  exercise.
    name: exercise_doom
    rating: 4
  
  This is another paragraph
  with some stuff in it.
  
  rosie.
    test: '[current-user:uid] == 1'
    yaps: 1
  
    This is starting outer Rosie content.
    
    exercise.
      name: exercise [site:name]
    
    rosie.
      yaps: 3
  
      This is inner Rosie content.
    
    /rosie.
  
    This is ending outer Rosie content.
    
  /rosie.
  
  
  exercise.
    name: exercise_bandicoot

  Dogs are the *best*!
";

    $result = $this->parserService->parse($source);
    return [
      '#markup' => $result,
    ];

  }

  public function u1() {
    $user = \Drupal\user\Entity\User::load(1);
    $result = $user->getUsername(). '<br>';
    $roles = $user->getRoles();
    $roleEntities = Role::loadMultiple($roles);
    $roleNames='';

    foreach($roleEntities as $indx => $roleEntity) {
      $roleName = $roleEntity->id();
      $result .= $roleName . ':<br>';
      foreach($roleEntity->getPermissions() as $indx2 => $permission) {
        $result .= $permission . ' <br>';
      }
    }

    return [
      '#markup' => $result,
    ];
  }


}
