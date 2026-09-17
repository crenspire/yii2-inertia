<?php

declare(strict_types=1);

namespace app\controllers;

use app\models\ContactForm;
use Crenspire\Yii2Inertia\Inertia;
use Yii;
use yii\web\Controller;
use yii\web\Response;

class ContactController extends Controller
{
    public function actionIndex(): Response
    {
        return Inertia::render('Contact');
    }

    public function actionStore(): Response
    {
        // Inertia posts JSON; the request component parses it (a JsonParser is registered automatically).
        $form = new ContactForm();
        if (!$form->load(Yii::$app->request->post(), '') || !$form->validate()) {
            Inertia::withErrors($form);

            return Inertia::back();
        }

        Inertia::flash('success', "Thanks {$form->name}, we will get back to you soon!");

        return $this->redirect(['contact/index']);
    }
}
