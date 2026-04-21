<html>
    <head>
        <title>Calamus Education</title>
    </head>
    <body style="background-color:#333">
     <!--#00ADEF-->
        <div style="padding:53.13% 0 0 0;position:relative;background-color:#333;width:100%;" id="iframe_div">
            <iframe src="{{$lesson->link}}&autoplay=1&loop=0&autopause=1&muted=0" frameborder="0" allow="autoplay; fullscreen; picture-in-picture" allowfullscreen style="position:absolute;top:0;left:0;width:100%;height:100%;" 
                  title="">
            </iframe>
            
        </div>
        
        <script src="https://player.vimeo.com/api/player.js"></script>
        
        <script>
            var iframe = document.querySelector('iframe');
            var player = new Vimeo.Player(iframe);
       
            player.on('play', function() {
                player.setVolume(1);
            });
            
            let lastUpdate = 0;

            player.on('timeupdate', function(data) {
                const now = Date.now();
                // Only update if at least 1000ms (1 second) has passed
                if (now - lastUpdate >= 1000) {
                    Android.onTimeUpdate(String(Math.floor(data.seconds)));
                    lastUpdate = now;
                }
            });
    
            player.getVideoTitle().then(function(title) {
              console.log('title:', title);
            });
    
            player.getDuration().then(function(duration) {
                console.log('duration: ',duration)
               
            });
            
                        
            player.on('ended', ()=>{
                console.log('video has ended');
                Android.videoEnded("video ended");
            });
            
            // player.on('timeupdate', (data)=>{
            //     let currentTime = Math.floor(data.seconds.toFixed(2));
                
            // })
            
            Promise.all([
                player.getVideoWidth(),
                player.getVideoHeight()
            ]).then(function(dimensions) {
                var width = dimensions[0];
                var height = dimensions[1];
                console.log('Dimensions:', width + 'x' + height);
                if(height>=width){
                    
                    var iframe_div = document.getElementById('iframe_div');
                    iframe_div.style.height = iframe_div.style.height+150; // turn on for new version
                    Android.onVideoOrientation("p");
                }else{
                    Android.onVideoOrientation("L");
                    
                }
            });
           
        </script>
    </body>
</html>