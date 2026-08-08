<?php
/* BISM4RCK-KUN3H0 2026 */
require_once __DIR__ . '/../app/bootstrap.php';
require_role('admin');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validate();
    try {
        UiSettingsModel::save(trim($_POST['setting_key'] ?? ''), $_POST, (int)current_user()['id']);
        flash_set('success','Customization saved.');
    } catch (Throwable $e) { flash_set('danger',$e->getMessage()); }
    redirect('admin/settings.php');
}
View::render('admin/settings', ['pageTitle'=>'Customize Interface','settings'=>UiSettingsModel::all()]);
