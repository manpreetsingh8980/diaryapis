<!DOCTYPE html>
<html>
  <head>
    <meta charset="utf-8">
    <title></title>
  </head>
  <body>
  <div class="heading" style="font-size:30px; margin-top:30px;"><b>Customer Information : </b></div>
  
  @if(!empty($get_customer_details))
   <div class="heading" style="">ID                : {{$get_customer_details['id']}}</div>
   <div class="heading" style="">Name              : {{$get_customer_details['name']}}</div>
   <div class="heading" style="">Address           : {{$get_customer_details['address']}}</div>
   <div class="heading" style="">Phone Number      : {{$get_customer_details['phone_number']}}</div>
   <div class="heading" style="">Total milk(kg)    : {{$bill['total_weight']}}</div>
   <div class="heading" style="">Total amount      : {{$bill['total_amount']}}</div>
   <div class="heading" style="">Date              : {{$bill['start_date']}} to {{$bill['end_date']}}</div>
   @endif
  
	
	 <div class="heading" style="font-size:30px; margin-top:30px;"><b>Milk Entry Details : </b></div>

	@if(!empty($get_allentries))
	<table class="bordered">
		<tr class="font-12">
			<th style="width: 20px">S.no.</th>
			<th style="width: 20px">Weight</th>
			<th style="width: 20px">Fat</th>
			<th style="width: 20px">SNF</th>
			<th style="width: 20px">Amount</th>
			<th style="width: 20px">Type</th>
			<th style="width: 20px">Time</th>
			<th style="width: 30px">Date</th>
		</tr>
	   
		@foreach($get_allentries as $key=>$value)
		<tr>
			<td style="width: 20px">{{ ($key+1) }}</td>
			<td style="width: 20px">{{$value['weight']}}</td>
			<td style="width: 20px">{{$value['fat']}}</td>
			<td style="width: 20px;">
				{{$value['snf']}}
			</td>
			<td style="width: 20px;">
				{{$value['total_amount']}}
			</td>
			<td style="width: 20px">
				@if($value['type'] == '1') Cow @else Buffalo @endif
			</td>
			<td style="width: 20px">
				@if($value['time'] == '1') Morning @else Evening @endif
			</td>
			<td style="width: 30px">
			   {{date('Y-m-d', strtotime($value['created_at']))}}
			</td>
		</tr>
		@endforeach
	</table>
	@endif

	
  </body>
</html>
