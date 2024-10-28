<?php

/* Pass in by reference! */
function graph_mem_report ( &$rrdtool_graph ) {

    global $conf,
           $context,
           $range,
           $rrd_dir,
           $size;

    if ($conf['strip_domainname']) {
       $hostname = strip_domainname($GLOBALS['hostname']);
    } else {
       $hostname = $GLOBALS['hostname'];
    }

    $title = 'Memory';
    $rrdtool_graph['title'] = $title;
    $rrdtool_graph['lower-limit'] = '0';
    $rrdtool_graph['vertical-label'] = 'Bytes';
    $rrdtool_graph['extras'] = '--base 1024';
    $rrdtool_graph['height'] += ($size == 'medium') ? 28 : 0;

    if ( $conf['graphreport_stats'] ) {
        $rrdtool_graph['height'] += ($size == 'medium') ? 4 : 0;
        $rmspace = '\\g';
    } else {
        $rmspace = '';
    }
    $rrdtool_graph['extras'] .= ($conf['graphreport_stats'] == true) ? ' --font LEGEND:7' : '';

    if ($size == 'small') {
       $eol1 = '\\l';
       $space1 = ' ';
       $space2 = '         ';
    } else if ($size == 'medium' || $size = 'default') {
       $eol1 = '';
       $space1 = ' ';
       $space2 = '';
    } else if ($size == 'large') {
       $eol1 = '';
       $space1 = '                 ';
       $space2 = '                 ';
    }

    $bmem_shared_defs = '';
    $bmem_slab_defs = '';
    $bmem_buffers_defs = '';
    $bmem_used_cdef = "CDEF:'bmem_used'='bmem_total','bmem_free',-,'bmem_cached',-";
    $bmem_available_defs = '';
    $bmem_realused_cdef = '';

    if (file_exists("$rrd_dir/mem_shared.rrd")) {
       $bmem_used_cdef .= ",'bmem_shared',UN,0,'bmem_shared',IF,-";
       $bmem_shared_defs = "DEF:'mem_shared'='${rrd_dir}/mem_shared.rrd':'sum':AVERAGE "
           ."CDEF:'bmem_shared'=mem_shared,1024,* ";
    }

    if (file_exists("$rrd_dir/mem_sreclaimable.rrd")) {
       $bmem_used_cdef .= ",'bmem_slab',UN,0,'bmem_slab',IF,-";
       $bmem_slab_defs = "DEF:'mem_slab'='${rrd_dir}/mem_sreclaimable.rrd':'sum':AVERAGE "
           ."CDEF:'bmem_slab'=mem_slab,1024,* ";
    }

    if (file_exists("$rrd_dir/mem_buffers.rrd")) {
       $bmem_used_cdef .= ",'bmem_buffers',UN,0,'bmem_buffers',IF,-";
       $bmem_buffers_defs = "DEF:'mem_buffers'='${rrd_dir}/mem_buffers.rrd':'sum':AVERAGE "
           ."CDEF:'bmem_buffers'=mem_buffers,1024,* ";
    }

    if (file_exists("$rrd_dir/mem_available.rrd")) {
       $bmem_realused_cdef = "CDEF:'bmem_realused'='bmem_total','bmem_available',- ";
       $bmem_available_defs = "DEF:'mem_available'='${rrd_dir}/mem_available.rrd':'sum':AVERAGE "
           ."CDEF:'bmem_available'=mem_available,1024,* ";
    }

    $series = "DEF:'mem_total'='${rrd_dir}/mem_total.rrd':'sum':AVERAGE "
        ."CDEF:'bmem_total'=mem_total,1024,* "
        .$bmem_shared_defs
        ."DEF:'mem_free'='${rrd_dir}/mem_free.rrd':'sum':AVERAGE "
        ."CDEF:'bmem_free'=mem_free,1024,* "
        ."DEF:'mem_cached'='${rrd_dir}/mem_cached.rrd':'sum':AVERAGE "
        ."CDEF:'bmem_cached'=mem_cached,1024,* "
        .$bmem_slab_defs
        .$bmem_buffers_defs
        .$bmem_available_defs
        .$bmem_realused_cdef
        ."$bmem_used_cdef "
        ."AREA:'bmem_used'#${conf['mem_used_color']}:'Use${rmspace}' ";

    if ( $conf['graphreport_stats'] ) {
        $series .= "CDEF:used_pos=bmem_used,0,INF,LIMIT " 
                . "VDEF:used_last=used_pos,LAST "
                . "VDEF:used_min=used_pos,MINIMUM " 
                . "VDEF:used_avg=used_pos,AVERAGE " 
                . "VDEF:used_max=used_pos,MAXIMUM " 
                . "GPRINT:'used_last':'   ${space1}Now\:%6.1lf%s' "
                . "GPRINT:'used_min':'${space1}Min\:%6.1lf%s${eol1}' "
                . "GPRINT:'used_avg':'${space2}Avg\:%6.1lf%s' "
                . "GPRINT:'used_max':'${space1}Max\:%6.1lf%s\\l' ";
    }

    if (file_exists("$rrd_dir/mem_shared.rrd")) {
        $series .= "STACK:'bmem_shared'#${conf['mem_shared_color']}:'Share${rmspace}' ";

        if ( $conf['graphreport_stats'] ) {
            $series .= "CDEF:shared_pos=bmem_shared,0,INF,LIMIT "
                    . "VDEF:shared_last=shared_pos,LAST "
                    . "VDEF:shared_min=shared_pos,MINIMUM " 
                    . "VDEF:shared_avg=shared_pos,AVERAGE " 
                    . "VDEF:shared_max=shared_pos,MAXIMUM " 
                    . "GPRINT:'shared_last':' ${space1}Now\:%6.1lf%s' "
                    . "GPRINT:'shared_min':'${space1}Min\:%6.1lf%s${eol1}' "
                    . "GPRINT:'shared_avg':'${space2}Avg\:%6.1lf%s' "
                    . "GPRINT:'shared_max':'${space1}Max\:%6.1lf%s\\l' ";
        }
    }

    $series .= "STACK:'bmem_cached'#${conf['mem_cached_color']}:'Cache${rmspace}' ";

    if ( $conf['graphreport_stats'] ) {
        $series .= "CDEF:cached_pos=bmem_cached,0,INF,LIMIT "
                . "VDEF:cached_last=cached_pos,LAST "
                . "VDEF:cached_min=cached_pos,MINIMUM " 
                . "VDEF:cached_avg=cached_pos,AVERAGE " 
                . "VDEF:cached_max=cached_pos,MAXIMUM " 
                . "GPRINT:'cached_last':' ${space1}Now\:%6.1lf%s' "
                . "GPRINT:'cached_min':'${space1}Min\:%6.1lf%s${eol1}' "
                . "GPRINT:'cached_avg':'${space2}Avg\:%6.1lf%s' "
                . "GPRINT:'cached_max':'${space1}Max\:%6.1lf%s\\l' ";
    }

    if (file_exists("$rrd_dir/mem_sreclaimable.rrd")) {
        $series .= "STACK:'bmem_slab'#${conf['mem_slab_color']}:'Slab${rmspace}' ";

        if ( $conf['graphreport_stats'] ) {
            $series .= "CDEF:slab_pos=bmem_slab,0,INF,LIMIT "
                    . "VDEF:slab_last=slab_pos,LAST "
                    . "VDEF:slab_min=slab_pos,MINIMUM " 
                    . "VDEF:slab_avg=slab_pos,AVERAGE " 
                    . "VDEF:slab_max=slab_pos,MAXIMUM " 
                    . "GPRINT:'slab_last':'  ${space1}Now\:%6.1lf%s' "
                    . "GPRINT:'slab_min':'${space1}Min\:%6.1lf%s${eol1}' "
                    . "GPRINT:'slab_avg':'${space2}Avg\:%6.1lf%s' "
                    . "GPRINT:'slab_max':'${space1}Max\:%6.1lf%s\\l' ";
        }
    }

    if (file_exists("$rrd_dir/mem_buffers.rrd")) {
        $series .= "STACK:'bmem_buffers'#${conf['mem_buffered_color']}:'Buffer${rmspace}' ";

        if ( $conf['graphreport_stats'] ) {
            $series .= "CDEF:buffers_pos=bmem_buffers,0,INF,LIMIT "
                    . "VDEF:buffers_last=buffers_pos,LAST "
                    . "VDEF:buffers_min=buffers_pos,MINIMUM " 
                    . "VDEF:buffers_avg=buffers_pos,AVERAGE " 
                    . "VDEF:buffers_max=buffers_pos,MAXIMUM " 
                    . "GPRINT:'buffers_last':'${space1}Now\:%6.1lf%s' "
                    . "GPRINT:'buffers_min':'${space1}Min\:%6.1lf%s${eol1}' "
                    . "GPRINT:'buffers_avg':'${space2}Avg\:%6.1lf%s' "
                    . "GPRINT:'buffers_max':'${space1}Max\:%6.1lf%s\\l' ";
        }
    }

    if (file_exists("$rrd_dir/mem_free.rrd")) {
        $series .= "STACK:'bmem_free'#${conf['mem_free_color']}:'Free${rmspace}' ";

        if ( $conf['graphreport_stats'] ) {
            $series .= "CDEF:free_pos=bmem_free,0,INF,LIMIT "
                    . "VDEF:free_last=free_pos,LAST "
                    . "VDEF:free_min=free_pos,MINIMUM " 
                    . "VDEF:free_avg=free_pos,AVERAGE " 
                    . "VDEF:free_max=free_pos,MAXIMUM " 
                    . "GPRINT:'free_last':'  ${space1}Now\:%6.1lf%s' "
                    . "GPRINT:'free_min':'${space1}Min\:%6.1lf%s${eol1}' "
                    . "GPRINT:'free_avg':'${space2}Avg\:%6.1lf%s' "
                    . "GPRINT:'free_max':'${space1}Max\:%6.1lf%s\\l' ";
        }
    }

    if (file_exists("$rrd_dir/swap_total.rrd")) {
        $series .= "DEF:'swap_total'='${rrd_dir}/swap_total.rrd':'sum':AVERAGE "
                . "DEF:'swap_free'='${rrd_dir}/swap_free.rrd':'sum':AVERAGE "
                . "CDEF:'bmem_swapped'='swap_total','swap_free',-,1024,* "
                . "STACK:'bmem_swapped'#${conf['mem_swapped_color']}:'Swap${rmspace}' ";

    	if ( $conf['graphreport_stats'] ) {
                $series .= "CDEF:swapped_pos=bmem_swapped,0,INF,LIMIT "
                        . "VDEF:swapped_last=swapped_pos,LAST "
                        . "VDEF:swapped_min=swapped_pos,MINIMUM " 
                        . "VDEF:swapped_avg=swapped_pos,AVERAGE " 
                        . "VDEF:swapped_max=swapped_pos,MAXIMUM " 
                        . "GPRINT:'swapped_last':'  ${space1}Now\:%6.1lf%s' "
                        . "GPRINT:'swapped_min':'${space1}Min\:%6.1lf%s${eol1}' "
                        . "GPRINT:'swapped_avg':'${space2}Avg\:%6.1lf%s' "
                        . "GPRINT:'swapped_max':'${space1}Max\:%6.1lf%s\\l' ";
	}
    }

    if (file_exists("$rrd_dir/mem_available.rrd")) {
        $series .= "LINE2:'bmem_realused'#${conf['mem_available_color']}:'Avail${rmspace}' ";

        if ( $conf['graphreport_stats'] ) {
            $series .= "CDEF:available_pos=bmem_available,0,INF,LIMIT "
                    . "VDEF:available_last=available_pos,LAST "
                    . "VDEF:available_min=available_pos,MINIMUM " 
                    . "VDEF:available_avg=available_pos,AVERAGE " 
                    . "VDEF:available_max=available_pos,MAXIMUM " 
                    . "GPRINT:'available_last':' ${space1}Now\:%6.1lf%s' "
                    . "GPRINT:'available_min':'${space1}Min\:%6.1lf%s${eol1}' "
                    . "GPRINT:'available_avg':'${space2}Avg\:%6.1lf%s' "
                    . "GPRINT:'available_max':'${space1}Max\:%6.1lf%s\\l' ";
        }
    }

    $series .= "LINE2:'bmem_total'#${conf['cpu_num_color']}:'Total${rmspace}' ";

    if ( $conf['graphreport_stats'] ) {
        $series .= "CDEF:total_pos=bmem_total,0,INF,LIMIT "
                . "VDEF:total_last=total_pos,LAST "
                . "VDEF:total_min=total_pos,MINIMUM " 
                . "VDEF:total_avg=total_pos,AVERAGE " 
                . "VDEF:total_max=total_pos,MAXIMUM " 
                . "GPRINT:'total_last':' ${space1}Now\:%6.1lf%s' "
                . "GPRINT:'total_min':'${space1}Min\:%6.1lf%s${eol1}' "
                . "GPRINT:'total_avg':'${space2}Avg\:%6.1lf%s' "
                . "GPRINT:'total_max':'${space1}Max\:%6.1lf%s\\l' ";
    }

    // If metrics like mem_used and mem_shared are not present we are likely not collecting them on this
    // host therefore we should not attempt to build anything and will likely end up with a broken
    // image. To avoid that we'll make an empty image
    if ( !file_exists("$rrd_dir/mem_used.rrd") && !file_exists("$rrd_dir/mem_shared.rrd") ) 
      $rrdtool_graph[ 'series' ] = 'HRULE:1#FFCC33:"No matching metrics detected"';   
    else
      $rrdtool_graph[ 'series' ] = $series;

    return $rrdtool_graph;
}

?>
