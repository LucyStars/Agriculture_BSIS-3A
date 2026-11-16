<?php
// The route table: the "route" in the address (index.php?route=farmers) => [controller class, method].

return [
    'index'            => [DashboardController::class, 'index'],
    'login'            => [AuthController::class,      'login'],
    'logout'           => [AuthController::class,      'logout'],

    'farmers'          => [FarmerController::class,    'index'],
    'farmer_form'      => [FarmerController::class,    'form'],
    'farmer_view'      => [FarmerController::class,    'view'],
    'farmer_delete'    => [FarmerController::class,    'delete'],

    'farms'            => [FarmController::class,      'index'],
    'farm_form'        => [FarmController::class,      'form'],
    'farm_view'        => [FarmController::class,      'view'],
    'farm_delete'      => [FarmController::class,      'delete'],

    'crops'            => [CropController::class,      'index'],
    'crop_form'        => [CropController::class,      'form'],
    'crop_view'        => [CropController::class,      'view'],
    'crop_delete'      => [CropController::class,      'delete'],

    'plantings'        => [PlantingController::class,  'index'],
    'planting_form'    => [PlantingController::class,  'form'],
    'planting_view'    => [PlantingController::class,  'view'],
    'planting_delete'  => [PlantingController::class,  'delete'],

    'harvests'         => [HarvestController::class,   'index'],
    'harvest_form'     => [HarvestController::class,   'form'],
    'harvest_view'     => [HarvestController::class,   'view'],
    'harvest_delete'   => [HarvestController::class,   'delete'],

    'users'            => [UserController::class,      'index'],
    'user_form'        => [UserController::class,      'form'],

    'reports'          => [ReportController::class,    'index'],
    'report_farmers'   => [ReportController::class,    'farmers'],
    'report_crops'     => [ReportController::class,    'crops'],
    'report_status'    => [ReportController::class,    'status'],
    'report_harvests'  => [ReportController::class,    'harvests'],
    'report_quantity'  => [ReportController::class,    'quantity'],
    'report_value'     => [ReportController::class,    'value'],
];
