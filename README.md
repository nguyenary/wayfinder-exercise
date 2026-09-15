<center><h1>wayfinder-exercise</h1></center>

> Người thực hiện: Nguyễn Trường Nguyên<br>
> Em xin trả lời bài test bằng tiếng Việt

## PART A Reading the code, risk and priorities
### A.1 List every problem you can see in the code above. For each one, add a sentence: if it were left as-is, what would actually go wrong?

1. API GET `/enquiries` và không có bất kỳ phương thức Authentication nào. 

    **Hậu quả:** Đây là API cho Dashboard nhân viên nếu bất kỳ ai biết URL cũng có thể xem toàn bộ tên/email/số điện thoại khách hàng => Lộ dữ liệu cá nhân

2. Cả API POST `/enquiries` không có bất kỳ phương thức validate nào.
    
    **Hậu quả:** Khiến cho bất kỳ ai cũng có thể ghi dữ liệu sai định dạng vào DB thông qua API này

3. API GET `/enquiries` đang list tất cả các record có trong `TourEnquiry`. Nặng hơn là trong `foreach` gọi `$enquiry->tour->name`

    **Hậu quả:** Nếu tương lai data nhiều lên thì sẽ khiến API response chậm và tương lai có thể làm tăng tải cho DB nên cần phải có `limit / page`. Nặng hơn là trong `foreach` gọi `$enquiry->tour->name` gây ra vấn vấn đề **N+1 query** khiến query càng thêm chậm.

4. API POST `/enquiries` nhận và ghi tất cả các fiels trong request

    **Hậu quả:** Vì `$fillable` trong `TourEnquiry` có cả `'status'` và controller dùng `TourEnquiry::create($request->all())` vì thế khách hàng gửi form có thể tự nhét `"status": "booked"` (hoặc bất kỳ giá trị nào) vào request và tự set trạng thái enquiry của chính họ.

5. Cả 2 API đang chỉ xử lý trường hợp success không xử lý trường hợp error hoặc Exception

    **Hậu quả:** Khi `tour_id` không tồn tại (vi phạm FK) hoặc thiếu field bắt buộc, Laravel sẽ ném `QueryException/500 Internal Server Error thô`, có thể lộ SQL, tên bảng, cấu trúc DB nếu `APP_DEBUG=true` gây xấu UX (khách hàng thấy lỗi khó hiểu) vừa là lộ thông tin của hệ thống.

6. Không có rate limiting/throttle trên POST `/enquiries`

    **Hậu quả:** Vì đây là form công khai nên có thể bị spam/bot submit hàng loạt, không có gì chặn. Khiến dữ liệu trong DB sẽ có nhiều data rác và gây highload hệ thống.

### A.2 Assume this is your first day at Wayfinder and you have one day for this module. Pick the three things you would do first, in the order you would do them, and explain why that order.

Nếu đây là ngày đầu tiên của em ở Wayfinder và em có một ngày để làm việc với module này, ba việc đầu tiên em sẽ làm theo thứ tự sau:

1. Việc đầu tiên em sẽ làm là chặn quyền truy cập vào API liệt kê danh sách enquiry, vì đây là trang dashboard nội bộ cho nhân viên Sales nhưng lại đang mở công khai, ai cũng xem được thông tin cá nhân của khách hàng như tên, email, số điện thoại. Đây là rủi ro lớn nhất vì nó đang thực sự lộ dữ liệu khách hàng ngay lúc này, và việc thêm xác thực cho một endpoint nội bộ cũng không mất nhiều thời gian, nên em sẽ ưu tiên làm trước.

2. Việc thứ hai em sẽ làm là sửa lại API nhận form từ khách hàng, thêm validate cho dữ liệu đầu vào và chặn không cho phép khách hàng tự gửi lên trạng thái của enquiry. Đây là lỗi nghiêm trọng vì hiện tại ai gửi form cũng có thể tự đặt trạng thái enquiry của mình thành bất kỳ giá trị nào, ví dụ tự đặt thành đã đặt tour, trong khi thực tế Sales còn chưa liên hệ khách hàng đó. Việc này ảnh hưởng trực tiếp đến quy trình làm việc thật của đội Sales nên em xếp ưu tiên thứ hai, ngay sau vấn đề lộ dữ liệu.

3. Việc thứ ba em sẽ làm là bổ sung xử lý lỗi cho cả hai API, để khi có dữ liệu không hợp lệ hoặc có lỗi hệ thống thì trả về thông báo rõ ràng cho người dùng, thay vì để lộ ra những thông tin kỹ thuật như câu lệnh SQL hay cấu trúc database. Em làm việc này sau cùng vì nó gắn liền với phần validate ở bước hai, làm luôn trong lúc sửa API sẽ hợp lý hơn là tách ra làm riêng.

### A.3 For each item you picked in A.2: do you need any further information from Wayfinder before you can be confident it is the right call? If so, what information? If not, briefly say why not.

1. Với việc đầu tiên là thêm xác thực cho trang dashboard, em cần biết thêm là công ty hiện đã có sẵn hệ thống đăng nhập cho nhân viên nội bộ chưa và ai là người được phép xem trang này, chỉ có Sales hay còn có phòng ban khác. Nếu công ty đã có sẵn cơ chế đăng nhập dùng chung cho các trang quản trị khác thì em sẽ nối vào hệ thống đó thay vì tự làm riêng một cơ chế xác thực mới cho module này, tránh làm rối thêm hệ thống.

2. Với việc thứ hai là sửa API nhận form thì em khá tự tin có thể làm ngay mà không cần hỏi thêm. Vì bốn trạng thái được phép chuyển đã được nêu rõ trong đề bài và việc không cho khách hàng tự đặt trạng thái là một quy tắc nghiệp vụ hợp lý không phụ thuộc vào thông tin nào khác của công ty.

3. Với việc thứ ba là xử lý lỗi em muốn biết công ty có sẵn một chuẩn chung nào cho việc trả lỗi ở các API khác trong hệ thống hay không để làm theo cho thống nhất tránh mỗi API trả lỗi một kiểu khác nhau.

### A.4 Name at least two things you would deliberately not do that day, and explain why.

1. Việc đầu tiên em sẽ không làm trong ngày đầu tiên là sửa lỗi hiệu năng ở API liệt kê enquiry, chỗ đang bị gọi nhiều câu truy vấn thừa khi lấy tên tour. Vấn đề này chỉ thực sự ảnh hưởng khi dữ liệu lớn lên, còn hiện tại chưa gây ảnh hưởng thật đến người dùng, nên em sẽ để lại làm sau khi đã xử lý xong các vấn đề về bảo mật và nghiệp vụ quan trọng hơn.

2. Việc thứ hai em sẽ không làm là viết lại toàn bộ module theo một kiến trúc mới mà em thấy tốt hơn. Em mới vào công ty một ngày, chưa hiểu hết những phần khác của hệ thống có đang phụ thuộc vào cách code hiện tại hay không nên việc sửa nhỏ, đúng trọng tâm sẽ an toàn hơn nhiều so với việc viết lại trên diện rộng khi chưa nắm rõ bối cảnh.

## PART B Implementation and testing

### B.1 The public form: Make the endpoint that receives submissions from the public form accept only data that is valid and safe to store.

### B.2 Status update endpoint

### B.3 Automated tests

