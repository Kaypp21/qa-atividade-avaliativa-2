describe('Página inicial', () => {
  it('carrega com sucesso', () => {
    cy.visit('/');
    cy.get('body').should('be.visible');
  });
});
 
describe('Bibliotecas', () => {
  it('exibe a listagem e o link para criar uma nova biblioteca', () => {
    cy.visit('/bibliotecas');
    cy.contains('Criar Nova Biblioteca').should('be.visible');
    cy.get('table').should('exist');
  });
 
  it('permite criar uma nova biblioteca pelo formulário', () => {
    const nome = `Biblioteca Teste ${Date.now()}`;
 
    cy.visit('/bibliotecas/new');
 
    cy.get('#nome').type(nome);
    cy.get('#endereco').type('Rua dos Testes, 123');
    cy.get('#telefone').type('11999999999');
    cy.get('#email').type('teste@biblioteca.local');
    cy.get('#created_by').select(1); // seleciona o primeiro responsável da lista
 
    cy.contains('Criar Biblioteca').click();
 
    // após criar, a aplicação redireciona para a listagem
    cy.url().should('include', '/bibliotecas');
    cy.contains(nome).should('be.visible');
  });
});