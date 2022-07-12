<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\Employe;
use App\Entity\Produits;
use App\Entity\Categories;
use App\Form\EmpConnexionType;
use App\Form\ProduitType;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class AppController extends AbstractController
{
    private $session;

    /**
     * @Route("/app", name="app_app")
     */
    public function index(): Response
    {
        return $this->render('app/index.html.twig', [
            'controller_name' => 'AppController',
        ]);
    }
    /**
     * @Route("/login", name="app_login")
     */
    // Ecran de connexion
    public function login(Request $request, $employe= null) : Response
    {
        if ($employe==null) {
            $employe = new Employe();
        }
        $form = $this->createForm(EmpConnexionType::class, $employe);
        // récupération de la requête
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->flush();
            // return $this->redirectToRoute('app_connexion', array('login'=> $employe->getLogin(),'mdp' => $employe->getMdp()));
            return $this->redirectToRoute('app_connexion', array('login'=> $employe->getLogin(),'mdp' => $employe->getMdp()));
        }
        return $this->render('app/accueil.html.twig', array('form'=>$form->createView()));
    }    

    /**
     * @Route("/connexion/{login},{mdp}", name="app_connexion")
     */
    // On vérifie si le login et le mot de passe sont corrects.Si oui alors on renvoie l'utilisateur à l'interface de gestion de produits.
    public function verifIdentifiants(Request $request,SessionInterface $session,$login,$mdp)
    {
        $user = $this->getDoctrine()->getRepository(Employe::class)->findOneBy(array ('login' => $login, 'mdp' => md5($mdp)));
        if ($user) {
            $statut=$user->getStatut();
            if($statut > 0){
                $session = new Session();
                $session->start();
                $session->set('connected',1);
                return $this->redirectToRoute('app_liste_produits');
                return $this->render('formation/listeform.html.twig',array('lesForms' => $formations , 'message' => $message , 'messageI' => $messageI , 'messageA' => $messageA ,'messageB' => $messageB , 'insc' => $inscriptions , 'inscA' => $inscriptionsA, 'inscB' => $inscriptionsB,'lesEmployes'=>$employes));
            }
        }
        else{
            return $this->redirectToRoute('app_login');
        }
    }

    /**
     * @Route("/accueiladmin", name="app_liste_produits")
     */
    // Vue d'ensemble des produits existants.
    public function afficherListeProduits(Request $request,SessionInterface $session)
    {   
        $verifSession = $session->get('connected');
        if($verifSession!=1){
           return $this->redirectToRoute('app_login');
        }
        $produits = $this->getDoctrine()->getRepository(Produits::class)->findAll();
                if (!$produits) {
                    $message ="Aucun produit !";
                }
                else {
                    $message = null;
                } 
        $categories = $this->getDoctrine()->getRepository(Categories::class)->findAll();
                if (!$categories) {
                    $messageC ="Aucune catégorie de produits ! ";
                }
                else {
                    $messageC = null;
                } 
        return $this->render('app/listeproduits.html.twig',array('lesProduits' => $produits ,'lesCategories' => $categories,'message' => $message,'messageC' => $messageC));
    }

    /**
     * @Route("/ajoutProduit", name="app_ajoutProduit" )
     */
    // Ajoute un produit dans la liste des produits
    public function ajoutProduit(Request $request, $produit= null)
    {   
        if ($produit==null) {
            $produit = new Produits();
        }
        $form = $this->createForm(ProduitType::class, $produit);
        // récupération de la requête
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($produit);
            $em->flush();
            return $this->redirectToRoute('app_liste_produits');
        }
        
        return $this->render('app/gestionproduit.html.twig', array('form'=>$form->createView()));
    }

    /**
     * @Route("/modifProduit/{id}", name="app_modifProduit" )
     */
    // Modifie les informations d'un produit,on récupère l'id du produit
    public function modifProduit(Request $request,$id)
    {   
        $produit = $this->getDoctrine()->getRepository(Produits::class)->find($id);
        if ($produit==null) {
            $produit = new Produits();
        }
        $form = $this->createForm(ProduitType::class, $produit);
        // récupération de la requête
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($produit);
            $em->flush();
            return $this->redirectToRoute('app_liste_produits');
            }
        
        return $this->render('app/gestionproduit.html.twig', array('form'=>$form->createView()));
    }

    /**
     * @Route("/suppProduit/{id}", name="app_suppProduit" )
     */
    // Supprime un produit,on récupère l'id du produit
    public function suppProduit(Request $request,$id)
    {   
       $produit = $this->getDoctrine()->getRepository(Produits::class)->find($id);
       $manager = $this->getDoctrine()->getManager();
       $manager->remove($produit);
       $manager->flush();
       echo "Produit supprimé";
       return $this->redirectToRoute('app_liste_produits');
    }

    /**
     * @Route("/listeproduits/{id}", name="app_triProduit")
     */
    // Vue d'ensemble des produits existants selon la catégorie choisie,on récupère l'id de la catégorie.
    public function afficherListeProduitSelonCateg(Request $request,SessionInterface $session,$id)
    {   

        $verifSession = $session->get('connected');
        if($verifSession!=1){
           return $this->redirectToRoute('app_login');
        }
        $produits = $this->getDoctrine()->getRepository(Produits::class)->findBy(array ("categorie"=>$id));
        // Nom de la catégorie affichée sur la liste des produits
        $categorie = $this->getDoctrine()->getRepository(Categories::class)->find($id);
        //////
                if (!$produits) {
                    $message ="Aucun produit !";
                }
                else {
                    $message = null;
                } 
        $categories = $this->getDoctrine()->getRepository(Categories::class)->findAll();
                if (!$categories) {
                    $messageC ="Aucune catégorie de produits ! ";
                }
                else {
                    $messageC = null;
                } 
        return $this->render('app/listeproduitsparcateg.html.twig',array('lesProduits' => $produits ,'lesCategories' => $categories,'message' => $message,'messageC' => $messageC,'categorie'=>$categorie));
    }
}
