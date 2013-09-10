"=============================================================================
" AUTHOR:  Mun Mun Das <m2mdas at gmail.com>
" FILE: symfony_routes.vim
" Last Modified: September 10, 2013
" License: MIT license  {{{
"     Permission is hereby granted, free of charge, to any person obtaining
"     a copy of this software and associated documentation files (the
"     "Software"), to deal in the Software without restriction, including
"     without limitation the rights to use, copy, modify, merge, publish,
"     distribute, sublicense, and/or sell copies of the Software, and to
"     permit persons to whom the Software is furnished to do so, subject to
"     the following conditions:
"
"     The above copyright notice and this permission notice shall be included
"     in all copies or substantial portions of the Software.
"
"     THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
"     OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
"     MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT.
"     IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY
"     CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT,
"     TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE
"     SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
" }}}
"=============================================================================


let s:save_cpo = &cpo
set cpo&vim

let s:Cache = unite#util#get_vital().import('System.Cache')

function! unite#sources#symfony_routes#define() "{{{
    let sources = [ s:symfony_routes_name, s:symfony_routes_pattern]
    return sources
endfunction"}}}

let s:symfony_routes_name = {
            \ 'name' : 'symfony/routes_by_name',
            \ 'description' : 'Lists routes by route name',
            \ 'hooks' : {},
            \ }

let s:symfony_routes_pattern = {
            \ 'name' : 'symfony/routes_by_pattern',
            \ 'description' : 'Lists routes by route pattern',
            \ 'hooks' : {},
            \ }

function! s:symfony_routes_name.gather_candidates(args, context) "{{{
    if !phpcomplete_extended#symfony#is_valid_project()
        return []
    endif
    return s:get_route_candidates(a:args, a:context, 'name')
endfunction"}}}

function! s:symfony_routes_pattern.gather_candidates(args, context) "{{{
    if !phpcomplete_extended#symfony#is_valid_project()
        return []
    endif
    return s:get_route_candidates(a:args, a:context, 'pattern')
endfunction"}}}

function! s:get_route_candidates(args, context, type) "{{{
    "TODO: make route info in a preview window
    "TODO: goto route definition
    let type = a:type
    let routes = phpcomplete_extended#symfony#get_routes()
    let route_keys = sort(keys(routes))
    let padded_route_keys = phpcomplete_extended#util#add_padding(copy(route_keys))
    if empty(routes)
        return []
    endif
    let candidates = map(route_keys, "{
                \ 'word' : type=='name'? routes[v:val].name : routes[v:val].path,
                \ 'abbr' : type=='name'? routes[v:val].name : routes[v:val].path,
                \ 'kind' : 'jump_list',
                \ 'action__path' : routes[v:val].controller.file,
                \ 'action__line' : routes[v:val].controller.start_line,
                \ }"
            \)

    return candidates
endfunction "}}}


let &cpo = s:save_cpo
unlet s:save_cpo

" vim: foldmethod=marker:expandtab:ts=4:sts=4:tw=78
