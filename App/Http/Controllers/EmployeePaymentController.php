<?php
namespace Modules\TomatoHr\App\Http\Controllers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use TomatoPHP\TomatoAdmin\Facade\Tomato;
use Modules\TomatoHr\App\Models\Employee;

class EmployeePaymentController extends Controller
{
    public string $model;

    public function __construct()
    {
        $this->model = \Modules\TomatoHr\App\Models\EmployeePayment::class;
    }

    /**
     * @param Request $request
     * @return View|JsonResponse
     */
    public function index(Request $request): View|JsonResponse
    {
        return Tomato::index(
            request: $request,
            model: $this->model,
            view: 'tomato-hr::employee-payments.index',
            table: \Modules\TomatoHr\App\Tables\EmployeePaymentTable::class
        );
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function api(Request $request): JsonResponse
    {
        return Tomato::json(
            request: $request,
            model: \Modules\TomatoHr\App\Models\EmployeePayment::class,
        );
    }

    /**
     * @return View
     */
    public function create(): View
    {
        return Tomato::create(
            view: 'tomato-hr::employee-payments.create',
        );
    }

    /**
     * @param Request $request
     * @return RedirectResponse|JsonResponse
     */
    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $request->merge([
           "user_id" => auth('web')->user()->id,
            "date" => $request->get('date') ?? Carbon::now()->toDateTimeString()
        ]);
        $response = Tomato::store(
            request: $request,
            model: \Modules\TomatoHr\App\Models\EmployeePayment::class,
            validation: [
                'user_id' => 'nullable|exists:users,id',
                'employee_id' => 'required|exists:employees,id',
                'date' => 'nullable',
                'reason' => 'required|max:255|string',
                'type' => 'required|max:255|string',
                'description' => 'nullable|max:65535',
                'total' => 'required',
                'status' => 'nullable|max:255|string',
                'is_approved' => 'nullable'
            ],
            message: __('EmployeePayment updated successfully'),
            redirect: 'admin.employee-payments.index',
        );

        if($response instanceof JsonResponse){
            return $response;
        }

        return $response->redirect;
    }

    /**
     * @param \Modules\TomatoHr\App\Models\EmployeePayment $model
     * @return View|JsonResponse
     */
    public function show(\Modules\TomatoHr\App\Models\EmployeePayment $model): View|JsonResponse
    {
        return Tomato::get(
            model: $model,
            view: 'tomato-hr::employee-payments.show',
        );
    }

    /**
     * @param \Modules\TomatoHr\App\Models\EmployeePayment $model
     * @return View
     */
    public function edit(\Modules\TomatoHr\App\Models\EmployeePayment $model): View
    {
        return Tomato::get(
            model: $model,
            view: 'tomato-hr::employee-payments.edit',
        );
    }

    /**
     * @param Request $request
     * @param \Modules\TomatoHr\App\Models\EmployeePayment $model
     * @return RedirectResponse|JsonResponse
     */
    public function update(Request $request, \Modules\TomatoHr\App\Models\EmployeePayment $model): RedirectResponse|JsonResponse
    {
        $request->merge([
            "user_id" => auth('web')->user()->id,
            "date" => $request->get('date') ?? Carbon::now()->toDateTimeString()
        ]);


        $response = Tomato::update(
            request: $request,
            model: $model,
            validation: [
                'user_id' => 'nullable|exists:users,id',
                'employee_id' => 'sometimes|exists:employees,id',
                'date' => 'nullable',
                'reason' => 'sometimes|max:255|string',
                'type' => 'sometimes|max:255|string',
                'description' => 'nullable|max:65535',
                'total' => 'sometimes',
                'status' => 'nullable|max:255|string',
                'is_approved' => 'nullable'
            ],
            message: __('EmployeePayment updated successfully'),
            redirect: 'admin.employee-payments.index',
        );

        if($request->get('is_approved') && $model->status !== 'paid'){
            $employee  = Employee::find($model->employee_id);
            if($request->get('type') === 'in'){
                $employee->deposit($request->get('total'));
            }
            else {
                $employee->withdraw($request->get('total'));
            }

            $model->status = 'paid';
            $model->save();
        }

         if($response instanceof JsonResponse){
             return $response;
         }

         return $response->redirect;
    }

    /**
     * @param \Modules\TomatoHr\App\Models\EmployeePayment $model
     * @return RedirectResponse|JsonResponse
     */
    public function destroy(\Modules\TomatoHr\App\Models\EmployeePayment $model): RedirectResponse|JsonResponse
    {
        $response = Tomato::destroy(
            model: $model,
            message: __('EmployeePayment deleted successfully'),
            redirect: 'admin.employee-payments.index',
        );

        if($response instanceof JsonResponse){
            return $response;
        }

        return $response->redirect;
    }
}
