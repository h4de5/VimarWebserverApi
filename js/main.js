$(function() {
    // Handler for .ready() called.

    var ajaxFunc = function(elem) {
        console.log("data", {
                eid: elem.attr('data-element-id'), 
                sid: elem.attr('data-status-onoff-id'), 
                value: (elem.attr('data-status-onoff-data') == 1 ? 0 : 1)
            });

        $.ajax({url: "index.php?ajax", 
            method : "POST",
            data: {
                eid: elem.attr('data-element-id'), 
                sid: elem.attr('data-status-onoff-id'), 
                value: (elem.attr('data-status-onoff-data') == 1 ? 0 : 1)
            },
            beforeSend: function() {
                console.log("sending..");
            },
            success: function(result) {
                selector = ".element a.btn[data-element-id=" + elem.attr('data-element-id') + "]";
                console.log("selector", selector);
                foundelem = $(selector);
                console.log("foundelem", foundelem);
                var oldelement = foundelem.replaceWith(result);

                $(result).click(function(event) {
                    event.preventDefault();
                    ajaxFunc($(selector));
                });
            }, 
            complete: function() {
                console.log("complete.");
            }
        });
    };


    
    // using on. on parent element, so events are kept after replacing a.btn using ajax
    $("div.element").on('click','a.btn', {}, function(event) {
        event.preventDefault();

        //console.log("this1", this);
        elem = $(this);

        ajaxFunc($(this));
    });
});



