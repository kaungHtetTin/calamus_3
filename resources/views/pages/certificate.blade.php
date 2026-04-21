<!DOCTYPE html>
<html lang="en">

	<head>
		<meta charset="utf-8">		
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="viewport" content="width=device-width, shrink-to-fit=9">
		<meta name="description" content="CalamusEducation">
		<meta name="author" content="CalamusEducation">
		<title>Calamus | Certificate</title>
		
		<!-- Favicon Icon -->
		<link rel="icon" type="image/png" href="{{ asset('logo.png') }}">
		
		<!-- Stylesheets -->
		<link href='http://fonts.googleapis.com/css?family=Roboto:400,700,500' rel='stylesheet'>
        <link href="https://fonts.googleapis.com/css2?family=Rosario:wght@300;400;500;600;700&display=swap" rel="stylesheet"> 
		<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
		
        <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
   
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>

		<style>
			
        .font-bell{
             font-family: Bell MT, sans-serif;
        }

        .font-poppin-medium{
            font-family: Poppins Medium, sans-serif;
        }

        .font-poppin-semibold{
           
        }
        /* .font_bold{
            font-family: 'Rosario',Pyidaungsu , Poppins SemiBold, sans-serif;
        } */
        .font_bold{
            font-family: 'Rosario';
            font-weight:bold;
            
        }

        .certificate_of_completion{
            font-family: 'Rosario';
            font-weight:bold;
            font-size: 30px;
            letter-spacing: 5px;
        }

        .error_container{
            text-align:center;
            padding:50px;
            color:#aaa;
            font-size:16px;
            font-family: Poppins Medium, sans-serif;
        
        }

        .course{
            padding: 7px;
            margin-bottom:7px;
            cursor: pointer;
            color:#333;
        }

        .course:hover{
            background: #333;
            color:white;
        }

        .course .title{
            font-size:14px;
            font-family: 'Rosario';
        }

		</style>
	</head> 

<body style="@if(!$error) min-width:700px; @endif">
	<!-- Body Start -->
	<div class="wrapper _bg4586 _new89">		
		<div class="_215cd2">
            @if(!$error)
                <div class="container">
                    @php
                        $bgSrc = $certificate_bg ?? '';
                        if (is_string($bgSrc) && str_starts_with($bgSrc, 'http')) {
                            $bgSrc = route('certificate.image-proxy', ['url' => $bgSrc]);
                        }
                        $sealSrc = $certificate_seal ?? '';
                        if (is_string($sealSrc) && str_starts_with($sealSrc, 'http')) {
                            $sealHost = parse_url($sealSrc, PHP_URL_HOST);
                            if ($sealHost && strtolower($sealHost) !== strtolower(request()->getHost())) {
                                $sealSrc = route('certificate.image-proxy', ['url' => $sealSrc]);
                            }
                        } else {
                            $sealSrc = asset($sealSrc);
                        }
                    @endphp
                    <div id="captureArea" align="center" style="position:relative;width:650px; height:460px;margin:auto">
                        <img src="{{ $bgSrc }}" alt="" crossorigin="anonymous" style="width:100%; height:100%; object-fit: contain; display:block;">

                        <div class="certificate_of_completion" style="position:absolute;top:70px;width:100%;text-align:center">
                            CERTIFICATE OF COMPLETION
                        </div>

                        <div style="font-family: 'Rosario';position:absolute;top:125px;width:100%;text-align:center">
                            This is to certify that
                        </div>

                        <div class="font_bold" style="position:absolute;top:160px;font-size:30px;width:100%;text-align:center">
                            {{ $user['learner_name'] }}
                        </div>

                        <div style="position:absolute;top:188px;width:500px;left:75px;height:2px;background:black;margin:auto">

                        </div>

                         <div style="font-family: 'Rosario';position:absolute;top:203px;width:100%;text-align:center">
                            has completed the
                        </div>

                        <div class="font_bold" style="position:absolute;top:231px;font-size:22px;width:100%;text-align:center;">
                            {{ $course['certificate_title'] }}
                        </div>

                        <div style="font-family: 'Rosario';position:absolute;top:263px;width:100%;text-align:center">
                            on the {{ $platform }} platform by Calamus Education
                        </div>
                        
                        <img src="{{ $sealSrc }}" alt="" crossorigin="anonymous" style="position:absolute;bottom:45px;right:60px; width:110px; height:110px;">

                        <div style="position:absolute;bottom:36px;right:40px;font-size:13px;width:170px;text-align:center">
                            <span class="font_bold">Issued on {{ $certificate['formatted_date'] }}</span>
                        </div>

                        <div style="position:absolute;bottom:95px;left:28px;font-size:12px;text-align:left; font-family: 'Rosario'">
                            <span class="font_bold">Certificate ID : <span style="font-family: 'poppin'"> {{ $course['certificate_code'] . $certificate_id }} </span> </span> <br>
                            <span> Authorized by <strong>Calamus Education</strong> <br>
                            <span> <strong>Sca</strong>n the <strong>QR</strong> code <strong>bel</strong>ow to <strong>ver</strong>ify this <strong>cer</strong>tificate and <strong>vie</strong>w course <strong>con</strong>tent.
                        </div>

                        <div style="position:absolute;bottom:37px;left:35px;font-size:12px;width:55px; height:55px;">
                            <div id="qrcode"></div>
                        </div>
                    </div>

                    <br>
                    <div id="loading_bar"  class="main-loader">													
                        <div class="spinner">
                            <div class="bounce1"></div>
                            <div class="bounce2"></div>
                            <div class="bounce3"></div>
                        </div>																										
                    </div>

                    <br><br>

                    <div id="btn_download" style="padding:5px; background:#000;color:white;border-radius:5px;cursor:pointer;text-align:center;">
                        Download
                    </div>
                    <br><br>

                </div>
                <script>

                    var course_id = {{ $course_id }};
                    var user_id = {{ $user_id }};
                    var certificate_id = "{{ $certificate_id }}";
                    var image_id = '{{ $certificate_id }}';
                    
                    $(document).ready(function() {
                        $('#loading_bar').hide();
                        $('#btn_download').on('click', function() {
                             $('#loading_bar').show();
                            // Get resolution scale factor (default to 2x for high quality)
                            const scale = 10;
                            
                            // Configuration for html2canvas with resolution control
                            const config = {
                                scale: scale,
                                useCORS: true,
                                allowTaint: true,
                                backgroundColor: '#ffffff',
                                logging: false,
                                width: $('#captureArea')[0].scrollWidth,
                                height: $('#captureArea')[0].scrollHeight,
                                scrollX: 0,
                                scrollY: 0,
                                windowWidth: $('#captureArea')[0].scrollWidth * scale,
                                windowHeight: $('#captureArea')[0].scrollHeight * scale
                            };
                            
                            html2canvas($('#captureArea')[0], config).then(canvas => {
                                // Create an <a> element to trigger the download
                                let link = $('<a>').attr({
                                    href: canvas.toDataURL('image/png'),
                                    download: 'calamus-certificate-'+certificate_id+'.png'
                                });
                    
                                // Trigger the download
                                link[0].click();
                                $('#loading_bar').hide();
                            });
                        });
                    });

                    var qrcode = new QRCode(document.getElementById("qrcode"), {
                        text: `www.calamuseducation.com/qr.php?id=${certificate_id}`,
                        width: 55,
                        height: 55,
                        colorDark : "#000000",
                        colorLight : "#ffffff",
                        correctLevel : QRCode.CorrectLevel.M
                    });
                </script>

            @else
                <div class="container">
                    <div class="error_container">
                        <br><br><br><br><br>
                        <br>
                        {!! $error !!}
                        <br><br><br><br><br>
                        <br><br>
                    </div>
                </div>
            @endif
		</div>
	</div>

</body>
</html>
