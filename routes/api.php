<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FundCollectorController;
use App\Http\Controllers\BmiIdController;
use App\Http\Controllers\SuperadminController;
use App\Http\Controllers\TermsConditionsController;
use App\Http\Controllers\UserBeneficiaryController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\UserTransactionController;
use App\Http\Controllers\CompanyTransactionController;
use App\Http\Controllers\AmountCategoryController;
use App\Http\Controllers\UserExpenseController;
use App\Http\Controllers\TreasurerController;
use App\Http\Controllers\ZakatController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\BmiUsersController;
use App\Http\Controllers\CountryOfResidenceController;
use App\Http\Controllers\NationalIdProofController;
use App\Http\Controllers\MonthlySipController;
use App\Http\Controllers\MonthlySipDetailsController;
use App\Http\Controllers\UserInvestmentController;
use App\Http\Controllers\UserProfitController;
use App\Http\Controllers\UserAddressController;
use App\Http\Controllers\UserBankController;
use App\Http\Controllers\EibKunoozController;
use App\Http\Controllers\UserZakatController;
use App\Http\Controllers\CompanyInvestmentHistoryController;
use App\Http\Controllers\CompanyProfitController;
use App\Http\Controllers\NotificationController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// fund collector
Route::post('InsertFundCollector', [
    FundCollectorController::class,
    'insert_fundcollector',
]);
Route::get('getAllFundCollectors',[FundCollectorController::class,'getAllFundCollectors']);

//insert main fund collector
Route::post('InsertMainFundcollector', [
    FundCollectorController::class,
    'insert_mainfundcollector',
]);

Route::post('LoginFundCollector', [
    FundCollectorController::class,
    'loginfundcollector',
]);

Route::post('UpdateTransfer', [
    FundCollectorController::class,
    'update_transfertobank',
]);
Route::get('ListFundDetails', [
    FundCollectorController::class,
    'list_fund_details',
]);
Route::get('ListFundDetailsApproved', [
    FundCollectorController::class,
    'list_fund_detailsapproved',
]);

Route::get('GetInactiveMembers', [
    FundCollectorController::class,
    'get_inactive_members',
]);

Route::get('fundcollector_activity_user', [
    FundCollectorController::class,
    'fundcollector_activity_user',
]);
Route::get('fundcollector_activity_company', [
    FundCollectorController::class,
    'fundcollector_activity_company',
]);
Route::get('fundcollector_activity_all', [
    FundCollectorController::class,
    'fundcollector_activity_all',
]);
Route::get('selectfundcollector/{userid}', [
    FundCollectorController::class,
    'select_fundcollector',
]);
Route::get('mf_fundcollector', [
    FundCollectorController::class,
    'mf_fundcollector',
]);
// BMIP ID
Route::post('insertBmipId', [BmiIdController::class, 'insert']);
Route::get('getAllBmiId', [BmiIdController::class, 'select']);
Route::get('sendMailToUser/{id}', [BmiIdController::class, 'sendingMail']);
Route::get('re_sendmail/{id}', [BmiIdController::class, 're_sendmail']);

Route::get('forgotpassword', [BmiIdController::class, 'forgot_password']);
Route::post('otp_verify', [BmiIdController::class, 'otp_verify']);
Route::get('getPendingRequest', [BmiIdController::class, 'getPendingRequest']);
Route::get('verifyUser/{id}', [BmiIdController::class, 'verifyUser']);
Route::post('userLogin', [BmiIdController::class, 'login']);
Route::post('passwordupdate', [BmiIdController::class, 'passwordupdate']);
Route::post('UpdateSettled', [BmiIdController::class, 'update_status']);
Route::get('getUserDetails/{bmi_id}', [
    BmiIdController::class,
    'getUserDetails',
]);
Route::post('   ', [
    BmiIdController::class,
    'select_userlogin',
]);
Route::get('getUser/{userid}', [BmiIdController::class, 'getUser']);
Route::post('deactivatebySuperadmin', [
    BmiIdController::class,
    'update_status_approved',
]);

Route::get('getActiveMembersList', [
    BmiIdController::class,
    'getActiveMembersList',
]);
Route::get('getDeactiveMembersList', [
    BmiIdController::class,
    'getDeactiveMembersList',
]);
Route::get('getOverviewDetailsOfUser/{bmi_id}', [
    BmiIdController::class,
    'getOverviewDetailsOfUser',
]);
Route::get('getAccountSummary', [BmiIdController::class, 'getAccountSummary']);
Route::get('getAccountSummaryapp/{id}/{year}/{month}', [
    BmiIdController::class,
    'account',
]);
Route::get('bmipsummery/{bmi_id}/{year}/{month}', [BmiIdController::class, 'bmipsummery']);
Route::post('approved_inactive_members', [
    BmiIdController::class,
    'approved_inactive_members',
]);
Route::post('acceptTermsAndConditions', [
    BmiIdController::class,
    'acceptTermsAndConditions',
]);
//superadmin
Route::post('insertSuperadmin', [SuperadminController::class, 'insert']);
Route::post('updateSuperadmin', [SuperadminController::class, 'update']);
Route::get('deleteSuperadmin/{id}', [SuperadminController::class, 'delete']);
Route::post('passwordupdateSuperadmin', [
    SuperadminController::class,
    'passwordupdate',
]);
Route::post('loginSuperadmin', [
    SuperadminController::class,
    'loginSuperadmin',
]);
Route::get('getSuperAdmin', [SuperadminController::class, 'select']);

//TermsConditions
Route::post('insertTermsConditions', [
    TermsConditionsController::class,
    'insert',
]);
Route::post('updateTermsConditions', [
    TermsConditionsController::class,
    'update',
]);
Route::get('getTermsConditions', [TermsConditionsController::class, 'select']);

//user beneficiary
Route::post('InsertBeneficiary', [UserBeneficiaryController::class, 'insert']);
Route::post(
    'ImageUploadforDocument
',
    [
        UserBeneficiaryController::class,
        'imageUploadforDocument
',
    ]
);
Route::post('UpdateBeneficiary', [
    UserBeneficiaryController::class,
    'update_beneficiary',
]);

//company
Route::post('InsertCompany', [CompanyController::class, 'insert_company']);
Route::post('UpdateCompany', [CompanyController::class, 'update_company']);
Route::get('getCompanyDetails', [
    CompanyController::class,
    'getcompanydetails',
]);

Route::post('ApprovalCompanyInvestment', [
    CompanyController::class,
    'approve_investment',
]);
Route::get('checkInvestmentAvailability/{amount}', [
    CompanyController::class,
    'checkInvestmentAvailability',
]);
Route::get('FundApproval', [CompanyController::class, 'fund_approval']);
Route::get('getCompanyInvestmentBankOut', [
    CompanyController::class,
    'getCompanyInvestmentBankOut',
]);

Route::get('CompanyDetailsapp/{id}/{bmi}', [
    CompanyController::class,
    'CompanyDetailsapp',
]);
Route::get('Profitdetailsapp/{id}', [
    CompanyController::class,
    'Profitdetailsapp',
]);
Route::get('selectapprovedcompany',[CompanyController::class,'selectapprovedcompany']);
//insert user transaction
Route::post('InsertUserTransaction', [
    UserTransactionController::class,
    'insert',
]);

Route::get('fundtransferdetails_user', [
    UserTransactionController::class,
    'fundtransferdetails_user',
]);
Route::post('UpdateTransfer', [
    UserTransactionController::class,
    'update_transfertobank',
]);
Route::get('getCollectedMemberdetails', [
    UserTransactionController::class,
    'list_individual',
]);
Route::post('TransferToMainFundCollector_user', [
    UserTransactionController::class,
    'updateTransfer_user',
]);
Route::get('getTransferCollectionUser', [
    UserTransactionController::class,
    'getTransferCollection_user',
]);
Route::post('ApproveUserTransactionByTreasurer', [
    UserTransactionController::class,
    'ApproveUserTransactionByTreasurer',
]);
Route::post('transfertobankUser', [
    UserTransactionController::class,
    'transfertobank_user',
]);
Route::get('getApprovedUserTransaction', [
    UserTransactionController::class,
    'fund_received',
]);
Route::get('FundReceived', [UserTransactionController::class, 'fund_received']);
Route::get('expense_in', [UserTransactionController::class, 'expense_in']);
Route::get('monthlysip_in', [
    UserTransactionController::class,
    'monthlysip_in',
]);
Route::get('others_in', [UserTransactionController::class, 'others_in']);
Route::get('getusertransaction_approvelist', [
    UserTransactionController::class,
    'getusertransaction_approvelist',
]);
Route::get('getTransferUserTransactionDetails',[UserTransactionController::class,'getTransferUserTransactionDetails']);
Route::get('getVerifiedUserTransactionDetails',[UserTransactionController::class,'getVerifiedUserTransactionDetails']);
///insert company transaction
// company transaction
Route::post('InsertCompanyTransaction', [
    CompanyTransactionController::class,
    'insert',
]);
Route::post('UpdateCompanyTransaction', [
    CompanyTransactionController::class,
    'update_transaction',
]);
Route::get('fundtransferdetails_company', [
    CompanyTransactionController::class,
    'fundtransferdetails_company',
]);
Route::post('transferToMainFundCollector_company', [
    CompanyTransactionController::class,
    'updateTransferStatus',
]);
Route::get('getTransferCollectionDetails', [
    CompanyTransactionController::class,
    'getTransferCollectionDetails',
]);
Route::post('ApproveCompanyTransactionByTreasurer', [
    CompanyTransactionController::class,
    'ApproveCompanyTransactionByTreasurer',
]);
Route::get('getApprovedCompanyTransaction', [
    CompanyTransactionController::class,
    'getApprovedCompanyTransaction',
]);
Route::post('transferCompanyTransactionToBank', [
    CompanyTransactionController::class,
    'transferToBank',
]);
Route::get('company_transaction_collector',[CompanyTransactionController::class,'company_transaction_collector']);
Route::get('getBankInOfProfit',[CompanyTransactionController::class,'getBankInOfProfit']);
Route::get('getProfitSummary',[CompanyTransactionController::class,'getProfitSummary']);
Route::get('investmentreturnIn', [
    CompanyTransactionController::class,
    'investmentreturnIn',
]);
Route::get('getTransferCompanyTransactionDetails',[CompanyTransactionController::class,'getTransferCompanyTransactionDetails']);
Route::get('getVerifiedCompanyTransactionDetails',[CompanyTransactionController::class,'getVerifiedCompanyTransactionDetails']);

//Amount category
Route::post('InsertAmount', [AmountCategoryController::class, 'insert']);
Route::post('UpdateAmount', [AmountCategoryController::class, 'update']);
Route::get('ListAmount', [AmountCategoryController::class, 'list']);
Route::get('DeleteAmount/{id}', [AmountCategoryController::class, 'delete']);

//user expense
Route::post('InsertUserExpense', [UserExpenseController::class, 'insert']);
Route::post('UpdateUserExpense', [UserExpenseController::class, 'update']);
Route::get('listExpenseFund', [UserExpenseController::class,'listExpenseFund']);
Route::get('getUserExpense_m', [UserExpenseController::class, 'getUserExpense_m']);


Route::get('getUserExpense_m/{bmi_id}/{year}/{month}', [
    UserExpenseController::class,
    'getUserExpense_m',
]);

//Treasurer
Route::post('InsertTreasurer', [TreasurerController::class, 'insert']);
Route::post('loginTreasurer', [TreasurerController::class, 'loginTreasurer']);
Route::get('getTreasurer', [TreasurerController::class, 'getTreasurer']);
Route::get('selecttreasurer/{userid}', [TreasurerController::class, 'select']);
Route::get('Treasurer', [TreasurerController::class, 'select']);


// fund collector
Route::post('InsertFundCollector', [
    FundCollectorController::class,
    'insert_fundcollector',
]);
Route::get('getFundCollectors', [
    FundCollectorController::class,
    'getFundCollectors',
]);
Route::get('getMainFundCollectors', [
    FundCollectorController::class,
    'getMainFundCollectors',
]);
Route::get('getFundCollectorById',[FundCollectorController::class,'getFundCollectorById']);

//insert main fund collector
Route::post('InsertMainFundcollector', [
    FundCollectorController::class,
    'insert_mainfundcollector',
]);

//Zakat
Route::post('InsertZakat', [ZakatController::class, 'insert']);
Route::post('UpdateZakat', [ZakatController::class, 'update']);
Route::get('ListZakat', [ZakatController::class, 'list_zakat']);
Route::post('ApprovingZakat', [ZakatController::class, 'approving_zakat']);
Route::post('addZakat', [ZakatController::class, 'getZakat']);
//Expense
Route::post('InsertExpense', [ExpenseController::class, 'insert']);
Route::post('UpdateExpense', [ExpenseController::class, 'update']);
Route::get('ListExpense', [ExpenseController::class, 'list_expense']);
Route::post('TransferExpense', [ExpenseController::class, 'transfer_expense']);
Route::get('getExpesnseOut', [ExpenseController::class, 'get_expenseout']);
Route::get('getExpenseSummary', [
    ExpenseController::class,
    'getExpenseSummary',
]);
Route::get('getUserExpenseDetails/{bmi_id}', [
    ExpenseController::class,
    'getUserExpenseDetails',
]);

// Country of Residence
Route::post('insertCountryOfResidence', [
    CountryOfResidenceController::class,
    'insert',
]);
Route::get('getCountryOfResidence', [
    CountryOfResidenceController::class,
    'getAll',
]);
// National id proof
Route::post('insertNationalIdProof', [
    NationalIdProofController::class,
    'insert',
]);
Route::get('getNationalIdProof',[NationalIdProofController::class,'getAll']);

// BMI users
Route::post('insertBmiUsers', [BmiUsersController::class, 'insert']);
Route::post('updateProofDetails', [
    BmiUsersController::class,
    'updateProofDetails',
]);
Route::post('deleteProofDetails', [
    BmiUsersController::class,
    'deleteProofDetails',
]);
Route::post('addProofDetails', [BmiUsersController::class, 'addProofDetails']);
Route::post('updateBMIusers', [BmiUsersController::class, 'update_bmiusers']);
//MonthlySip
Route::post('insertMonthlySip', [MonthlySipController::class, 'insert']);
Route::post('updateMonthlySip', [MonthlySipController::class, 'update']);
Route::get('getMonthlySipList', [MonthlySipController::class, 'select']);
Route::get('AmountAvalilableinBank', [MonthlySipController::class, 'AmountAvalilableinBank']);
//MonthlySipDetails
Route::post('insertMonthlySipDetails', [
    MonthlySipDetailsController::class,
    'insert',
]);
Route::get('getSipbyId/{bmi_id}', [
    MonthlySipDetailsController::class,
    'list_sip',
]);

Route::get('list_monthly_sip_app', [
    MonthlySipDetailsController::class,
    'list_monthly_sip_app',
]);
Route::get('pending_advance', [
    MonthlySipDetailsController::class,
    'pending_advance',
]);

//User investment
Route::post('InsertUserInvestment', [
    UserInvestmentController::class,
    'insert',
]);
Route::get('ListUserInvestment', [
    UserInvestmentController::class,
    'list_investment',
]);
Route::get('ListMembers', [UserInvestmentController::class, 'list_members']);
Route::get('InvestmentOut', [
    UserInvestmentController::class,
    'investment_out',
]);
Route::get('investmentsummary', [
    UserInvestmentController::class,
    'investment_summary',
]);
Route::get('filter_ivestmentsummary',[UserInvestmentController::class,'filter_ivestmentsummary']);
Route::get('investment_details_app/{bmi_id}', [
    UserInvestmentController::class,
    'investment_details_app',
]);

Route::get('InvestedCompany/{bmi_id}/{year}/{month}', [
    UserInvestmentController::class,
    'InvestedCompany',
]);
Route::get('companytotalprofit/{company_id}/{year}/{month}', [
    UserInvestmentController::class,
    'companytotalprofit',
]);
Route::get('company_investment/{bmi_id}/{company_id}/{year}', [
    UserInvestmentController::class,
    'company_investment',
]);

//user profit
Route::post('InsertUserProfit', [UserProfitController::class, 'insert_profit']);
Route::post('UpdateUserProfit', [UserProfitController::class, 'Update_profit']);
Route::get('DeleteuserProfit/{id}', [
    UserProfitController::class,
    'delete_profit',
]);
Route::post('getUserProfitDetails', [
    UserProfitController::class,
    'getUserProfitDetails',
]);
Route::get('profit_in', [UserProfitController::class, 'profit_in']);
Route::get('profit_company_app/{bmi_id}/{company_id}', [
    UserProfitController::class,
    'profit_company_app',
]);
Route::get('profit_company_list_app/{bmi_id}/{company_id}', [
    UserProfitController::class,
    'profit_company_list_app',
]);

//user address
Route::post('insertUserAddress', [UserAddressController::class, 'insert']);
Route::post('update_useraddress',[UserAddressController::class,'update_useraddress']);

// user bank
Route::post('insertUserBank', [UserBankController::class, 'insert']);
Route::post('updateUserBank', [UserBankController::class, 'update']);

// Eib Kunooz
Route::post('insertEibKunooz', [EibKunoozController::class, 'insert']);
Route::get('listEibKunooz', [EibKunoozController::class, 'list']);

//userzakat
Route::post('listUserZakat/{id}', [
    UserZakatController::class,
    'listUserZakat',
]);
Route::get('list_profit', [CompanyTransactionController::class, 'list_profit']);
Route::get('getmonthlysip_treasurer', [
    MonthlySipController::class,
    'getmonthlysip_treasurer',
]);

//company_profit
Route::get('insert_companyprofit',[CompanyProfitController::class,'insert_companyprofit']);
Route::post('insertcompanyprofit',[CompanyProfitController::class,'insert']);
Route::get('profit_company_list_app', [
    CompanyProfitController::class,
    'getProfitDetails',
]);
// Company investment history 
Route::get('investment_company_app', [
    CompanyInvestmentHistoryController::class,
    'getInvestedDetails',
]);
// Notification Controller
Route::get('getNotification',[NotificationController::class,'getNotification']);
Route::post('viewNotification',[NotificationController::class,'viewNotification']);
