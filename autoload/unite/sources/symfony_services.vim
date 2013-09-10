"=============================================================================
" AUTHOR:  Mun Mun Das <m2mdas at gmail.com>
" FILE: symfony_services.vim
" Last Modified: August 29, 2013
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


function! unite#sources#symfony_services#define() "{{{
    let sources = [ s:symfony_services]
    return sources
endfunction"}}}

let s:symfony_services = {
            \ 'name' : 'symfony/services',
            \ 'description' : 'Lists Symfony services',
            \ 'hooks' : {},
            \ }

function! s:symfony_services.gather_candidates(args, context) "{{{
    if !phpcomplete_extended#symfony#is_valid_project()
        return []
    endif
    return s:get_services_menu_entries(a:args, a:context)
endfunction"}}}

function! s:get_services_menu_entries(args, context) "{{{
    let args = a:args
    let context = a:context
    let service_name = get(args, 0, '')
    let tag = get(args, 1, '')
    let services = phpcomplete_extended#symfony#get_services()['public']
    let tag_services = phpcomplete_extended#symfony#get_tag_services()
    if empty(tag_services)
        return []
    endif
    if empty(services)
        return []
    endif
    let service_keys = keys(services)
    if !empty(tag) && has_key(tag_services, tag)
        let service_keys = tag_services[tag]
        let services = filter(services, 'index(service_keys, v:key) != -1')
    endif
    let service_keys = sort(service_keys)
    let padded_service_keys = phpcomplete_extended#util#add_padding(copy(service_keys))
    let candidates = map(service_keys, "{
            \ 'word': v:val,
            \ 'abbr': printf('%s %s', padded_service_keys[index(service_keys, v:val)], services[v:val].service_fqcn),
            \ 'kind': 'file',
            \ 'action__path': services[v:val].service_file
            \}"
        \)
    return candidates
endfunction "}}}

let &cpo = s:save_cpo
unlet s:save_cpo

" vim: foldmethod=marker:expandtab:ts=4:sts=4:tw=78
