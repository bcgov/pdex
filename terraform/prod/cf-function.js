function handler(event) {
  var host =
    (event.request.headers.host &&
      event.request.headers.host.value) ||
    '';

  var originalURI = (event.request.uri && event.request.uri.value || '');
  
  var queryString = Object.keys(event.request.querystring)
    .map(key => key + '=' + event.request.querystring[key].value)
    .join('&');
    
  if (originalURI[0] == "/careertransitiontool" || originalURI[0] == "/careersearchtool") {
      return {
        statusCode: 301,
        statusDescription: 'Moved Permanently',
        headers: {
          location: {
            value:
            'https://www.pdex.gov.bc.ca' +
            event.request.uri +
            (queryString.length > 0 ? '?' + queryString : ''),
          }
        }
      };
    }
  

  if (host == "www.pdex.gov.bc.ca") {
    return event.request;
  }
    
  if (host == "pdex.gov.bc.ca") {
      return {
        statusCode: 301,
        statusDescription: 'Moved Permanently',
        headers: {
          location: {
            value:
            'https://www.pdex.gov.bc.ca' +
            event.request.uri +
            (queryString.length > 0 ? '?' + queryString : ''),
          },
        },
      };
  }

  return {
    statusCode: 404,
    statusDescription: 'Not found'
  };
}