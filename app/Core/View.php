<?php
declare(strict_types=1);
final class View  {
    public static function render(string $template,array $viewData=[]):void {
        extract($viewData,EXTR_SKIP);
        $title=$title??'FILKOM Connect';
        $currentUser=$viewData['_viewUser']??Auth::user();
        $unread=empty($viewData['_skipDatabase'])&&$currentUser?Notifikasi::belumDibaca((int)$currentUser['idAkun']):0;
        $formKey=query('r','dashboard').'|'.number('id');
        $GLOBALS['formValues']=$_SESSION['form_values'][$formKey]??[];
        unset($_SESSION['form_values'][$formKey]);
        $modalErrors=array_values((array)($errors??[]));
        if(!empty($error))$modalErrors[]=$error;
        foreach($_SESSION['flash']??[] as $flash)if(in_array($flash['type'],['error','danger'],true))$modalErrors[]=$flash['message'];
        require ROOT.'/app/Views/layout/header.php';
        require ROOT.'/app/Views/'.$template.'.php';
        require ROOT.'/app/Views/layout/footer.php';
    }
}
