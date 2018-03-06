<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Invoice;
use DB;
use App\Sms_trigger;
use Carbon\Carbon;

class PendingInvoice extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pending:invoice';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Triggers sms alerts for pending invoices';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $invoices = Invoice::select(DB::raw('*, DATE_ADD(created_at , INTERVAL 4 DAY) AS sms_start, DATE_ADD(created_at , INTERVAL 6 DAY) AS sms_end'))
                                ->havingRaw('CURDATE() >= sms_start AND CURDATE() <= sms_end')
                                ->whereIn('status',[0,2])
                                ->get();

        $sms_trigger = Sms_trigger::where('alias','=','pending_invoice')->first();
        $message = $sms_trigger->message;
        $sms_status = $sms_trigger->status;
        $sender_id = \Utilities::getSetting('sms_sender_id');

        foreach ($invoices as $invoice) 
        {
            if($invoice->Payment_details->contains('mode',1))
            {
                $sms_text = sprintf($message,$invoice->member->name,$invoice->pending_amount,$invoice->invoice_number);
                \Utilities::Sms($sender_id,$invoice->member->contact,$sms_text,$sms_status);
            }            
        }

    }
}
