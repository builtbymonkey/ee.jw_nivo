$ ->

    $nivo_table = $('.js-nivo-table tbody')

    #
    # Add Slide
    #
    $('.js-nivo-add-slide').on 'click', (e) ->
        e.preventDefault()

        console.log "add..."

        false

    #
    # Remove Slide
    #
    $nivo_table.on 'click', '.js-nivo-remove-slide', (e) ->
        e.preventDefault()

        $(this).closest('tr').remove()

        # Show 'no slides' if there are none
        $rows = $('tr', $nivo_table)
        if ($rows.length is 1)
            $('td', $rows).show()

        false

    #
    # Re-order Slides
    #
